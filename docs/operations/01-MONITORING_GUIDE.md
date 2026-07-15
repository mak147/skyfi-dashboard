# Operations Guide 01 — Monitoring & Observability Guide

**Phase:** 5 — Operations Documentation  
**Audience:** Site Reliability Engineers (SRE), Network Operations Center (NOC) Team, System Administrators  
**Status:** Production-Ready Standard  
**Last reviewed:** 2026-07-15

---

## 1.0 Executive Overview

The SkyFi ISP Management System operates as a high-density, real-time platform managing customer billing, PPPoE accounts, MikroTik router interactions, hotspot sessions, and inventory. To guarantee high availability, a comprehensive multi-layered monitoring strategy is implemented.

Observability is split into three core categories:
1.  **Infrastructure Monitoring:** System metrics (CPU, Memory, IOPS, Network) for host nodes, databases, and caches.
2.  **Application Observability (APM):** HTTP request rates, error ratios, query latency, background worker status.
3.  **Network Hardware Telemetry:** SNMP polling of MikroTik routers, interface bandwidth snapshots, PPPoE status, and ping checks.

---

## 2.0 Architectural Data Flow

Monitoring telemetry flows from various components into either our consolidated APM Platform (e.g., Datadog, New Relic) or our local PostgreSQL/MariaDB monitoring tables for low-latency status checks and automated alerts.

```
+---------------------------------------------------------------------------------+
|                                  DATA SOURCES                                   |
+---------------------------------------------------------------------------------+
   | (Host Agent)             | (SNMP / TCP)              | (Syslog / DB)
   v                          v                           v
+------------------+   +------------------+   +-----------------------------------+
| Host Metrics     |   | Router SNMP      |   | Application Logs / Audit Logs      |
| (CPU, RAM, Disk) |   | (Bps, Packets)   |   | (PHP Exception, Slow Queries)    |
+------------------+   +------------------+   +-----------------------------------+
   \                          /                           /
    \                        /                           /
     v                      v                           v
+---------------------------------------------------------------------------------+
|                             OBSERVABILITY PLATFORM                              |
+---------------------------------------------------------------------------------+
|  * Datadog Core Agent (TCP 8125 Daemon / Metrics Ingestion)                     |
|  * MariaDB Internal Monitoring Tables (Event Logs, Alerts)                      |
|  * Syslog / Docker daemon logs forwarding                                       |
+---------------------------------------------------------------------------------+
   |                                                                           |
   v (Trigger Criteria)                                                        v (Manual Drilldown)
+------------------+                                                      +-------------+
| PagerDuty / Slack|                                                      | SRE / NOC   |
| (Immediate Alert)|                                                      | Dashboards  |
+------------------+                                                      +-------------+
```

---

## 3.0 Internal Database-Driven Monitoring Tables

SkyFi utilizes native database schemas to log network events, device states, interface snapshots, and alerts. This ensures that operators can query the platform's state directly using standard SQL.

### 3.1 Primary Schema Structures

The monitoring database schema consists of several critical tables:
*   `monitoring_events`: High-frequency audit trail for operational events like `device_status_change`, `interface_status_change`, `threshold_violation`, etc.
*   `monitoring_device_status_history`: Latency and online status over time for managed MikroTik and generic network devices.
*   `monitoring_interface_snapshots`: Port-by-port traffic telemetry (RX/TX bytes, current bits-per-second, link status) polled directly from MikroTik API/SNMP.
*   `monitoring_alerts`: Tracks open, acknowledged, and resolved alerts inside the system.
*   `monitoring_sync_events`: Tracks the performance, execution time, and success rate of cron/worker tasks (e.g., PPPoE sync, interface poll).

### 3.2 SQL Queries for Real-Time Status

Operators can run the following SQL queries to audit the state of the network and application:

#### Identify Active Critical Alerts
```sql
SELECT id, alert_type, severity, title, description, triggered_at 
FROM monitoring_alerts 
WHERE status = 'new' AND severity = 'critical'
ORDER BY triggered_at DESC;
```

#### Get Router Sync Failure Rates (Last 24 Hours)
```sql
SELECT 
    router_id, 
    sync_type, 
    COUNT(*) as total_syncs,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failure_count,
    ROUND(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as failure_rate_pct,
    AVG(execution_time_ms) as avg_latency_ms
FROM monitoring_sync_events
WHERE created_at >= NOW() - INTERVAL 1 DAY
GROUP BY router_id, sync_type;
```

#### Identify Interface Snapshot Bottlenecks (Top 10 High-Traffic Interfaces)
```sql
SELECT router_id, interface_name, rx_bps, tx_bps, checked_at 
FROM monitoring_interface_snapshots 
WHERE running = 1 
ORDER BY (rx_bps + tx_bps) DESC 
LIMIT 10;
```

---

## 4.0 SNMP Hardware Telemetry (MikroTik Routers)

SkyFi polls RouterBOARDs and Cloud Core Routers (CCR) via SNMP v2c/v3 or via the MikroTik API. For low-overhead polling of interface metrics, SNMP is preferred.

### 4.1 Key SNMP OIDs for Monitoring
Configure your SNMP collector or agent to poll the following Object Identifiers (OIDs):

*   **System CPU Utilization:** `.1.3.6.1.4.1.14988.1.1.3.10.0` (Average load across CPU cores)
*   **System Free Memory:** `.1.3.6.1.2.1.25.2.3.1.6.1` (Free physical memory)
*   **System Health Voltage:** `.1.3.6.1.4.1.14988.1.1.3.8.0` (Input power voltage in Volts * 10)
*   **Interface Incoming Octets (Bytes):** `.1.3.6.1.2.1.2.2.1.10.[InterfaceIndex]`
*   **Interface Outgoing Octets (Bytes):** `.1.3.6.1.2.1.2.2.1.16.[InterfaceIndex]`
*   **Interface Link Operational Status:** `.1.3.6.1.2.1.2.2.1.8.[InterfaceIndex]` (1 = Up, 2 = Down)

### 4.2 Polling Configuration in `/backend/config/mikrotik.php`
Ensure that community strings and port values are strictly defined inside environmental configs, mapped through `config/mikrotik.php`:

```php
return [
    'snmp' => [
        'community' => env('MIKROTIK_SNMP_COMMUNITY', 'skyfi-telemetry-read'),
        'version' => '2c',
        'port' => 161,
        'timeout' => 3, // seconds
        'retries' => 2,
    ],
    'api' => [
        'port' => env('MIKROTIK_API_PORT', 8728),
        'tls_port' => env('MIKROTIK_API_TLS_PORT', 8729),
        'timeout' => 5,
    ]
];
```

---

## 5.0 Golden Signals & Alert Thresholds

We monitor the system using the **Four Golden Signals**: Latency, Traffic, Errors, and Saturation.

| Signal | Target Resource | Metric Source | Warning Threshold (P2) | Critical Threshold (P1) | Notification Action |
| --- | --- | --- | --- | --- | --- |
| **Latency** | API Response | ALB Target Response Time | `> 800ms` (p95, 5 mins) | `> 1500ms` (p99, 3 mins) | P2: Slack, P1: PagerDuty |
| **Latency** | Database Query | RDS Read/Write Latency | `> 50ms` (5 mins) | `> 200ms` (3 mins) | P2: Slack, P1: PagerDuty |
| **Traffic** | Web Server | Nginx Request Count | `> 5000 req/min` (Sudden) | `N/A` (Organic growth) | P2: Info Log |
| **Traffic** | Router Interfaces | SNMP Bandwidth Snapshots | `> 85%` Interface Capacity| `> 95%` Interface Capacity| P2: NOC Dashboard |
| **Errors** | REST API | HTTP 5xx Response Rate | `> 1%` of total traffic | `> 5%` of total traffic | P2: Slack, P1: PagerDuty |
| **Errors** | Core Workers | Supervisor Failures | `1 failure` in 10 mins | `> 5 failures` in 10 mins | P2: Email, P1: Slack + SMS |
| **Saturation** | API Servers | CPU / RAM Utilization | `> 80%` for 10 mins | `> 90%` for 5 mins | P2: Slack, P1: PagerDuty |
| **Saturation** | Database Host | Disk Space Remaining | `< 15%` available | `< 5%` available | P2: Slack, P1: PagerDuty |
| **Saturation** | Redis Cache | Redis Memory Util | `> 80%` of maxmemory | `> 95%` of maxmemory | P2: Slack, P1: PagerDuty |

---

## 6.0 Active Health & Readiness Endpoint Checkers

SkyFi utilizes container-native health checks exposing two JSON routes over public Nginx port `/healthz` and `/readyz`.

### 6.1 Liveness (`/healthz`)
Verifies that the Nginx server can forward requests and that PHP-FPM worker pools are active and listening.
*   **Endpoint:** `http://localhost/healthz` (Internal or external)
*   **Failure Condition:** HTTP status code other than `200 OK`.
*   **Orchestration Impact:** Container engine (Docker/Kubernetes) will restart the container.

### 6.2 Readiness (`/readyz`)
Performs active network/connection checks to secondary dependencies.
*   **Endpoint:** `http://localhost/readyz`
*   **Internal Diagnostics:**
    1.  Attempts a database fetch (`SELECT 1;` on MariaDB).
    2.  Attempts a Redis read/write check (`PING`).
    3.  Checks writable storage permissions in `/backend/storage`.
*   **Response Format (Healthy):**
    ```json
    {
      "status": "ok",
      "timestamp": "2026-07-15T17:05:00Z",
      "checks": {
        "database": "connected",
        "redis": "connected",
        "storage": "writable"
      }
    }
    ```
*   **Orchestration Impact:** If `/readyz` fails (HTTP `503 Service Unavailable`), the load balancer detaches the container from the target pool immediately, preventing broken requests from reaching users.

---

## 7.0 Manual Telemetry & Polling Actions

If automatic monitoring suffers lag, operators can trigger manual polls of hardware or run synchronous health audits.

### 7.1 Force Polling a MikroTik Router
You can force the background workers to fetch immediate device health metrics using the monitoring CLI:

```bash
docker compose -f docker-compose.prod.yml exec backend \
  php -r "
    require 'autoload.php';
    \$app = require 'config/app.php';
    \$service = \$app->get(SkyFi\Monitoring\Services\DeviceHealthPollingService::class);
    echo \$service->pollDevice(1) ? 'Success' : 'Failed';
  "
```

### 7.2 Clear Resolving Alerts
To bulk resolve alerts that have been cleared on physical routers but remain in status `new` in the DB:

```bash
docker compose -f docker-compose.prod.yml exec mariadb \
  mariadb -uroot -p -e "
    UPDATE monitoring_alerts 
    SET status = 'resolved', 
        resolved_at = CURRENT_TIMESTAMP, 
        resolution_notes = 'Operator forced resolution from CLI' 
    WHERE status = 'new' AND alert_type = 'interface_down';
  " skyfi
```

---

## 8.0 Alerts & Log Aggregation Troubleshooting

If you suspect Datadog or local logging daemon is dropping events:

1.  **Check Supervisor Logging Worker State:**
    ```bash
    docker compose -f docker-compose.prod.yml exec supervisor supervisorctl status
    ```
    Ensure `skyfi-monitoring-worker` is `RUNNING`.

2.  **Verify Application Log Location:**
    Logs are written directly to standard out (stdout) inside the Docker container to conform to **12-Factor App standards**.
    Verify via:
    ```bash
    docker compose -f docker-compose.prod.yml logs --tail=200 backend
    ```
3.  **Inspect PHP-FPM Error Log:**
    Inside the container, core FPM errors go to `/var/log/php-fpm.log`. Run:
    ```bash
    docker compose -f docker-compose.prod.yml exec backend tail -n 50 /var/log/php-fpm.log
    ```
