# Operations Guide 07 — Server Maintenance Guide

**Phase:** 5 — Operations Documentation  
**Audience:** System Administrators, SRE, DevOps Engineers  
**Status:** Production-Ready Standard  
**Last reviewed:** 2026-07-15

---

## 1.0 Document Scope & Goals

To guarantee long-term system stability, security compliance, and high availability, the underlying virtual and physical infrastructure hosting the SkyFi platform requires regular maintenance. 

This guide details the standard operational procedures for:
*   OS-level security patching and kernel updates.
*   Disk space management, log pruning, and database compaction.
*   Network interface configurations and firewall audits.
*   System backup validation and routine hardware health sweeps.

---

## 2.0 Security Patching & OS Upgrades

Operating system packages (particularly OpenSSL, SSH, and Docker daemon libraries) must be kept secure.

### 2.1 Standard Patching Cadence

*   **Critical Patches (e.g., CVSS score >= 9.0):** Applied within 24 hours of release.
*   **Routine Updates:** Applied once per calendar month during a scheduled maintenance window.

### 2.2 Host Patching Runbook (Ubuntu/Debian)

To patch the host server without impacting current services:

```bash
# 1. Force update local package repositories
sudo apt-get update

# 2. Check for upgradeable packages (dry-run)
sudo apt-get --just-print upgrade

# 3. Download and install security patches (excluding kernel reboots)
sudo apt-get install --only-upgrades -y

# 4. Perform an overall system upgrade (if authorized in maintenance window)
sudo apt-get dist-upgrade -y

# 5. Clean up stale dependency packages
sudo apt-get autoremove -y
sudo apt-get clean
```

### 2.3 Executing a Secure Reboot

If a kernel upgrade requires a host reboot:

1.  Place the SkyFi stack in maintenance mode (touch `storage/logs/maintenance.flag`).
2.  Stop the active Docker Compose services to commit data caches to disk:
    ```bash
    docker compose -f docker-compose.prod.yml down
    ```
3.  Initiate the host reboot:
    ```bash
    sudo reboot
    ```
4.  Once the host is back online, verify that the Docker daemon starts and boots the containers automatically (using `restart: unless-stopped` flags):
    ```bash
    docker compose -f docker-compose.prod.yml ps
    ```
5.  Remove the maintenance flag and verify system health.

---

## 3.0 Disk Maintenance & Log Rotation

Unmanaged disk consumption is one of the most common causes of unscheduled database outages. 

### 3.1 Docker Daemon Log Limiting

Ensure that Docker containers do not generate infinite log files. The global Docker daemon configuration file (`/etc/docker/daemon.json`) should be set to limit log sizes:

```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "100m",
    "max-file": "3"
  }
}
```

Apply the changes:
```bash
sudo systemctl daemon-reload
sudo systemctl restart docker
```

### 3.2 Linux Logrotate Configuration for App Logs

Custom application logs written to `/backend/storage/logs/*.log` must be rotated regularly using Linux `logrotate`.

Create a configuration file at `/etc/logrotate.d/skyfi`:

```text
/home/user/skyfi-dashboard/backend/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0664 www-data www-data
    sharedscripts
    postrotate
        docker compose -f /home/user/skyfi-dashboard/docker-compose.prod.yml exec -T backend kill -USR2 1 > /dev/null 2>&1 || true
    endscript
}
```

### 3.3 Disk Cleanup Commands

When disk capacity exceeds 80%, execute the following commands to free up storage space safely:

```bash
# 1. Deep clean Docker cache (removes unused images, build caches, and dangling volumes)
# WARNING: Do NOT use the --volumes flag unless you have verified database persistent mounts
docker system prune -af

# 2. Check the size of log folders
sudo du -sh /var/log/*
sudo journalctl --vacuum-size=1G

# 3. Identify large residual files on host
sudo find / -type f -size +100M -exec ls -lh {} + 2>/dev/null | sort -hr | head -n 10
```

---

## 4.0 Database Space Optimization & Table Compaction

Over time, high-volume tables (like `audit_logs`, `monitoring_events`, and `monitoring_interface_snapshots`) can become fragmented, wasting disk space.

### 4.1 SQL Maintenance Command: OPTIMIZE TABLE

Running `OPTIMIZE TABLE` on InnoDB tables reclaims empty storage pages and rebuilds indexes. Execute this command during low-traffic windows (e.g., 02:00 UTC) as it can lock tables temporarily:

```bash
docker compose -f docker-compose.prod.yml exec -t mariadb \
  mariadb -uroot -p -e "
    OPTIMIZE TABLE skyfi.monitoring_events;
    OPTIMIZE TABLE skyfi.monitoring_interface_snapshots;
    OPTIMIZE TABLE skyfi.audit_logs;
  "
```

### 4.2 Automated Log Pruning Script

Create a cron job that automatically purges historical monitoring events and interface snapshots older than 30 days:

```bash
# Edit crontab
crontab -e

# Add the following entry to execute daily at 01:00 UTC:
# 0 1 * * * docker exec -t skyfi-mariadb mariadb -uroot -pPASSWORD -e "DELETE FROM skyfi.monitoring_events WHERE created_at < NOW() - INTERVAL 30 DAY; DELETE FROM skyfi.monitoring_interface_snapshots WHERE checked_at < NOW() - INTERVAL 30 DAY;"
```

---

## 5.0 Network Interface & Port Configurations

For security and isolation, the host server must restrict external access.

### 5.1 Host Network Interface Rules

*   **External Interface (`eth0`):** Binds to public network paths. Only ports `80` (HTTP), `443` (HTTPS), and a customized high-range SSH port (e.g., `2222`) are exposed.
*   **Internal Interface (`eth1`):** Binds to local private backhauls or VPN networks (e.g., `10.0.0.0/8`). Used for router polling, SNMP, and administrative SSH access.

### 5.2 UFW Firewall Hardening Rules

Configure the Uncomplicated Firewall (UFW) on the production host:

```bash
# 1. Block all incoming connections by default
sudo ufw default deny incoming
sudo ufw default allow outgoing

# 2. Open standard SSH access (via port 2222)
sudo ufw allow 2222/tcp comment 'Hardened SSH Port'

# 3. Open HTTP/HTTPS for user access
sudo ufw allow 80/tcp comment 'Nginx HTTP'
sudo ufw allow 443/tcp comment 'Nginx HTTPS'

# 4. Open Router SNMP and API polling paths on internal network only
sudo ufw allow from 10.0.0.0/8 to any port 161 proto udp comment 'SNMP Polling'
sudo ufw allow from 10.0.0.0/8 to any port 8728 proto tcp comment 'MikroTik Router API'

# 5. Enable the firewall
sudo ufw enable
```

---

## 6.0 Scheduled Maintenance Calendar

| Task | Cadence | Execution Window | Impact Level |
| --- | --- | --- | --- |
| **Verify Backups** | Daily | Automated (03:00 UTC) | None |
| **Log Rotation Review** | Weekly | Sundays (01:00 UTC) | None |
| **Docker Pruning** | Monthly | First Saturday (02:00 UTC)| Low (Docker Engine load) |
| **Host Security Upgrades** | Monthly | Second Tuesday (03:00 UTC)| Medium (Graceful reload) |
| **Failover Drill** | Annually | Scheduled Weekend Window | High (DR Outage simulation) |
