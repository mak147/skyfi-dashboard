# Operations Guide 10 — Troubleshooting Guide

**Phase:** 5 — Operations Documentation  
**Audience:** SRE, Helpdesk Agents, NOC Operators, Systems Engineers  
**Status:** Production-Ready Reference  
**Last reviewed:** 2026-07-15

---

## 1.0 Document Scope & Diagnostics Flow

This Troubleshooting Guide is the first line of defense during system degradation or failure of the SkyFi ISP Management System. 

When responding to an incident, always follow this standard debugging sequence:

```
[ Step 1: Check Readiness (/readyz) ]
                 |
        +--------+--------+
        |                 |
    (Healthy)         (Fails)
        |                 |
        |                 v
        |        [ Step 2: Check Logs (Docker) ]
        |                 |
        +--------+--------+
                 |
                 v
[ Step 3: Isolate Dependency (DB / Redis / Router) ]
                 |
                 v
   [ Step 4: Apply Target Action ]
```

---

## 2.0 Common HTTP Error Codes & Resolutions

### 2.1 HTTP 502 Bad Gateway

The user's browser loads a white screen with `502 Bad Gateway`.

#### Cause:
Nginx is running successfully on port 80/443, but it cannot establish a connection to the PHP-FPM application socket on port 9000 inside the `backend` container.

#### Diagnostic Checks:
1.  Verify the status of the containers:
    ```bash
    docker compose -f docker-compose.prod.yml ps
    ```
2.  Check the Nginx container error log:
    ```bash
    docker compose -f docker-compose.prod.yml logs nginx | grep -i connect
    ```
    *(Look for: `connect() failed (111: Connection refused) while connecting to upstream`).*

#### Resolution Steps:
*   If the `backend` container is stopped or crashing, check its logs:
    ```bash
    docker compose -f docker-compose.prod.yml logs backend --tail=50
    ```
*   Force restart the backend container:
    ```bash
    docker compose -f docker-compose.prod.yml restart backend
    ```

---

### 2.2 HTTP 504 Gateway Timeout

The request hangs for 60 seconds and then terminates with a `504` error.

#### Cause:
Nginx forwarded the request to PHP-FPM, but PHP-FPM failed to respond before the timeout. This is usually caused by:
*   A locked database query block.
*   A third-party API timeout (e.g., waiting for an unresponsive payment gateway or MikroTik router connection).

#### Diagnostic Checks:
1.  Check the PHP-FPM error log inside the backend container:
    ```bash
    docker compose -f docker-compose.prod.yml logs --tail=100 backend
    ```
2.  Inspect MariaDB process lists to see if queries are hanging:
    ```bash
    docker compose -f docker-compose.prod.yml exec mariadb \
      mariadb -uroot -p -e "SHOW PROCESSLIST;" skyfi
    ```

#### Resolution Steps:
*   **Kill hanging database threads:** If a query has been in the `Query` state for hundreds of seconds, terminate its process ID:
    ```sql
    KILL [PROCESS_ID];
    ```
*   **Increase Nginx fastcgi timeout temporarily:** if a heavy report calculation requires more time:
    ```nginx
    # In nginx config:
    fastcgi_read_timeout 300s;
    ```
*   **Restart PHP-FPM worker pool:**
    ```bash
    docker compose -f docker-compose.prod.yml exec backend kill -USR2 1
    ```

---

### 2.3 HTTP 401 Unauthorized / 403 Forbidden

Admin or Customer logins fail with unauthorized errors, or requests to specific paths return `403 Forbidden`.

#### Cause:
*   The system time is out of sync (causing JWT token generation to compute invalid signatures or expirations).
*   The JWT Secret keys were rotated but not reloaded in all instances.
*   The database RBAC table permissions are missing or out of sync.

#### Diagnostic Checks:
1.  Check host system time:
    ```bash
    date -u
    ```
    *(Ensure NTP sync is active and accurate).*
2.  Verify permissions inside the database:
    ```sql
    SELECT p.name FROM permissions p 
    JOIN permission_role pr ON p.id = pr.permission_id 
    JOIN roles r ON pr.role_id = r.id 
    WHERE r.name = 'Super Administrator';
    ```

#### Resolution Steps:
*   If permissions are missing, force re-seed the authentication tables:
    ```bash
    docker compose -f docker-compose.prod.yml exec backend php database/seed.php
    ```
*   Synchronize host NTP time:
    ```bash
    sudo timedatectl set-ntp on
    ```

---

## 3.0 Database-Specific Issues (MariaDB)

### 3.1 Scenario: "Too many connections"

Clients receive an error: `PDOException: SQLSTATE[HY000] [1040] Too many connections`.

#### Cause:
The database connection pool has been exhausted. This is caused by slow-running queries blocking connections or `max_connections` being set too low.

#### Resolution Steps:
1.  Log in to MariaDB as root:
    ```bash
    docker compose -f docker-compose.prod.yml exec mariadb mariadb -uroot -p
    ```
2.  Increase the connection limit immediately:
    ```sql
    SET GLOBAL max_connections = 500;
    ```
3.  Add the setting to `/etc/mysql/my.cnf` to ensure it persists across container restarts:
    ```ini
    max_connections = 500
    ```

---

### 3.2 Scenario: Transaction Deadlocks

The log files show: `SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction`.

#### Cause:
Two separate processes are trying to write/lock the same database rows in different orders (e.g., active billing runs and concurrent manual customer edits).

#### Diagnostic Checks:
```sql
SHOW ENGINE INNODB STATUS;
```
*(Look for the `LATEST DETECTED DEADLOCK` block to find the exact tables and SQL statements that conflicted).*

#### Resolution Steps:
*   Our framework uses automatic query retries for transactional blocks. Ensure that any custom background CLI loops catch lock exceptions and retry after a random backoff (e.g., 50ms to 200ms).
*   Optimize indexes so that lock queries access minimal rows.

---

## 4.0 Redis Cache Exhaustion

The logs show: `OOM command not allowed when used memory > 'maxmemory'`.

#### Cause:
Redis has filled up its allocated RAM cache, and the current eviction policy is set to prevent deletions.

#### Diagnostic Checks:
```bash
docker compose -f docker-compose.prod.yml exec redis redis-cli info memory
```
*(Check `used_memory_human` and `maxmemory_human`).*

#### Resolution Steps:
1.  Ensure that the eviction policy is set to `volatile-lru` or `allkeys-lru` (see [08 — Performance Tuning Guide](./08-PERFORMANCE_TUNING_GUIDE.md)).
2.  If memory must be freed immediately to restore services:
    ```bash
    docker compose -f docker-compose.prod.yml exec redis redis-cli flushall
    ```
    *(Note: This clears active sessions, forcing users to re-authenticate, but resolves the immediate crash).*

---

## 5.0 Router Polling & MikroTik API Timeouts

The Monitoring Dashboard shows a router as **Offline** or logs show: `Connection timed out to MikroTik API`.

#### Cause:
*   The router's IP address has changed or port forwarding is broken.
*   The MikroTik API service (`api` or `api-ssl`) is disabled inside RouterOS.
*   The router's local firewall block is dropping traffic from the application server.

#### Diagnostic Network Commands:
Execute these commands from the production backend host to isolate the issue:

```bash
# 1. Ping the router IP address (assumes router IP is 10.150.10.1)
ping -c 3 10.150.10.1

# 2. Test RouterOS API Port
nc -zv -w3 10.150.10.1 8728

# 3. Test RouterOS API-SSL Port
nc -zv -w3 10.150.10.1 8729

# 4. Check SNMP connectivity (verifies community string and UDP transport)
snmpwalk -v2c -c skyfi-telemetry-read 10.150.10.1 .1.3.6.1.4.1.14988.1.1.3.10
```

#### Resolution Steps on MikroTik RouterOS CLI:
If network tests fail, log in to the RouterOS terminal and check service states:

```routeros
# 1. Ensure API services are active and listening on correct ports
/ip service print

# 2. If disabled, enable the services
/ip service enable api,api-ssl

# 3. Ensure SNMP is active and the community string matches
/snmp print
/snmp community print
```
 Ensure that your MikroTik IP firewall rules allow incoming traffic on ports `8728`/`8729` (API) and `161` (SNMP) from the SkyFi backend host IP address.
