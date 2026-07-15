# SkyFi Operations & Playbook Documentation

**Phase:** 5 — Operations Documentation  
**Audience:** Site Reliability Engineers (SRE), DevOps Engineers, System Administrators, Network Operations Center (NOC) Engineers  
**Status:** Production-Ready Standard  
**Last reviewed:** 2026-07-15

This directory houses the comprehensive, enterprise-grade operations manual and runbooks for the **SkyFi ISP Management System**. These documents define standard operating procedures (SOPs), safety checklists, recovery playbooks, and systems administration tasks necessary to guarantee the high availability, security, and performance of the SkyFi platform in a production environment.

The procedures outlined here are specifically mapped to the real-world architecture of the SkyFi modular monolith, which runs as a containerized stack utilizing PHP 8.3 FPM, Nginx, MariaDB 11.4, Redis 7.4, and Supervisor-controlled background workers.

---

## Document Index

| Document | Focus Area | Contents |
| --- | --- | --- |
| [01 — Monitoring Guide](./01-MONITORING_GUIDE.md) | Observability & Telemetry | Datadog/CloudWatch metrics, network SNMP polling, active/passive alerts, log aggregation. |
| [02 — Backup Guide](./02-BACKUP_GUIDE.md) | Data Protection & Durability | Automated schedules, database dumps, application configuration backups, off-site replication. |
| [03 — Restore Procedures](./03-RESTORE_PROCEDURES.md) | Data Recovery & Rollback | Step-by-step SQL/file restoration, checksum validation, point-in-time recovery (PITR). |
| [04 — Disaster Recovery Runbook](./04-DISASTER_RECOVERY_RUNBOOK.md) | Business Continuity | Regional failover, Pilot Light strategy execution, DNS Route 53 switchover, failback. |
| [05 — Incident Response Playbook](./05-INCIDENT_RESPONSE.md) | Crisis & Outage Management | Severity levels, triage protocols, communication channels, root-cause analysis (RCA). |
| [06 — Upgrade & Rollback Guide](./06-UPGRADE_PROCEDURES.md) | Deployment Operations | Zero-downtime database migrations, blue-green deployment checklist, immediate rollback triggers. |
| [07 — Server Maintenance Guide](./07-SERVER_MAINTENANCE_GUIDE.md) | Systems & Hardware Operations | Security patching, OS upgrades, disk compaction, log rotation, network interface config. |
| [08 — Performance Tuning Guide](./08-PERFORMANCE_TUNING_GUIDE.md) | Capacity & Optimization | PHP-FPM sizing, MariaDB InnoDB buffer pool config, Redis eviction strategies, database indexing. |
| [09 — Security Checklist](./09-SECURITY_CHECKLIST.md) | Compliance & Hardening | Secrets rotation, IAM least privilege, TLS enforcement, rate limiting, vulnerability scanning. |
| [10 — Troubleshooting Guide](./10-TROUBLESHOOTING_GUIDE.md) | Diagnostics & Issue Mitigation | Common errors (502 Bad Gateway, locks, Redis memory limit), network diagnostics, trace patterns. |

---

## Production Architecture Snapshot

```
                            [ User Browser / Portal Mobile App ]
                                             |
                                    ( HTTPS / TLS Port 443 )
                                             |
                                    [ Cloud Load Balancer ]
                                             |
                                     ( HTTP / Port 80 )
                                             |
                                      [ Nginx Container ]
                                       /               \
                       ( Static Assets )               ( FastCGI Proxy )
                                     /                   \
                  [ React SPA Built Code ]             [ PHP-FPM API Runtime ]
                                                               |
                                            +------------------+------------------+
                                            |                  |                  |
                                     [ MariaDB 11.4 ]     [ Redis 7.4 ]     [ MikroTik Routers ]
                                    (App Database State)  (Cache & Sessions) (Network Provisioning)
```

---

## Key Contacts & Escalation Matrix

In the event of an incident or disaster, the following roles must be filled according to the protocols defined in [05 — Incident Response Playbook](./05-INCIDENT_RESPONSE.md):

*   **Incident Commander (IC):** Responsible for coordinating all active technical investigation and remediation.
*   **Communications Lead (CL):** Responsible for stakeholder notification, updating status pages, and customer outreach.
*   **Lead DevOps/DBA Engineer:** Responsible for hands-on restoration, backup execution, and infrastructure recovery.
*   **Lead Network Engineer:** Responsible for MikroTik edge router state, SNMP metrics, and local ISP backhaul connectivity.

---

## Quick Reference Commands

For emergency diagnostics, these commands can be executed immediately from the production server root directory:

```bash
# 1. Check Container Health
docker compose -f docker-compose.prod.yml ps

# 2. View Backend API Errors (Last 100 Lines)
docker compose -f docker-compose.prod.yml logs --tail=100 backend

# 3. View Nginx Web Server Access/Error Logs
docker compose -f docker-compose.prod.yml logs --tail=100 nginx

# 4. Check Redis Live Memory Usage
docker compose -f docker-compose.prod.yml exec redis redis-cli info memory

# 5. Check MariaDB Active Process List
docker compose -f docker-compose.prod.yml exec mariadb mariadb -uroot -p -e "SHOW PROCESSLIST;"
```

Refer to [10 — Troubleshooting Guide](./10-TROUBLESHOOTING_GUIDE.md) for detailed diagnostics flowcharts.
