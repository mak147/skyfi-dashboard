# Operations Guide 04 — Disaster Recovery Runbook

**Phase:** 5 — Operations Documentation  
**Audience:** Incident Commanders (IC), DevOps Lead, Lead Database Administrators, Network Operations Center (NOC)  
**Status:** Production-Ready Standard  
**Last reviewed:** 2026-07-15

---

## 1.0 Document Scope & Objectives

This Runbook defines the step-by-step procedures for recovering the SkyFi ISP Management System in the event of a catastrophic site outage, regional cloud failure, or physical data center disaster.

The ultimate objective of this plan is to meet our committed business continuity metrics:
*   **Recovery Point Objective (RPO):** `< 60 minutes` (Maximum acceptable data loss from last replicated transaction).
*   **Recovery Time Objective (RTO):** `< 4 hours` (Maximum acceptable duration to restore fully functional service to internal staff and end users).

---

## 2.0 Disaster Recovery Strategy: Pilot Light

To optimize operational costs while keeping RTO well within the 4-hour limit, SkyFi utilizes a **Pilot Light** deployment strategy in our secondary Disaster Recovery region (e.g., AWS `us-west-2` as backup for `us-east-1`, or a secondary bare-metal hypervisor cluster).

### 2.1 Regional Deployment Architecture

```
[ Primary Location: Active ]                     [ DR Location: Pilot Light Idle ]
     (us-east-1 / DC 1)                                (us-west-2 / DC 2)
  --------------------------                        ----------------------------
  * Load Balancer (Active)                          * Load Balancer (Idle)
  * Nginx + API Containers (Running)                * Nginx + API (Scaling Desired: 0)
  * MariaDB Master (Read/Write) ---> Replica ---->  * MariaDB Replica (ReadOnly / Ready to Promote)
  * Redis (Active Cache)                            * Redis (Idle / Standby)
  * Uploads S3 Bucket -------------> CRR ---------> * Uploads S3 Bucket (Replicated)
```

1.  **VPC & Core Network:** Identical networks are pre-provisioned via Infrastructure-as-Code (Terraform) in the DR region.
2.  **Database Replication:** MariaDB is configured with active asynchronous master-replica replication to the DR location.
3.  **File Storage Replication:** AWS S3 Cross-Region Replication (CRR) automatically copies customer attachments and invoice documents.
4.  **Application Tier:** Docker host servers exist in the DR location, but container replicas are set to `0` or idle to minimize CPU cost.

---

## 3.0 Phase 1: Disaster Declaration & Triage (T + 0 to T + 30 mins)

A disaster should only be declared if a core service is completely unavailable and cannot be recovered within 30 minutes using standard troubleshooting.

### 3.1 Authorized Declaring Officers
*   Chief Information Officer (CIO) / Chief Technology Officer (CTO)
*   VP of Network Operations
*   SRE Team Lead

### 3.2 Declaration Protocol

1.  **Convene Bridge:** SRE Lead opens an emergency Incident Response Bridge (Slack Channel `#emergency-bridge` / MS Teams meeting).
2.  **Verify Root Cause:** Confirm the issue is a systemic outage (e.g., AWS Region down, physical backbone severed) and not a transient software bug or routing glitch.
3.  **Formal Declaration:** The Declaring Officer issues the command:
    > "As of [17:15 UTC], I am formally declaring a Disaster Recovery event for the SkyFi Production Environment. We are executing the Pilot Light Failover Runbook to Region 2."

---

## 4.0 Phase 2: Active Failover Execution (T + 30 to T + 90 mins)

Once a disaster is declared, the technical recovery team executes the following four technical steps.

### Step 1: Promote the Database Replica

The MariaDB database in the DR region must be promoted to master. This breaks the replication link and allows writes.

On the DR database host:

```bash
# 1. Access the MariaDB container
docker compose -f docker-compose.prod.yml exec -t mariadb mariadb -uroot -p -e "
  -- Stop replica threads
  STOP SLAVE;
  RESET SLAVE ALL;
  -- Remove read-only lock
  SET GLOBAL read_only = OFF;
  SET GLOBAL super_read_only = OFF;
  SHOW SLAVE STATUS\G
"
```

Verify that the database is now in read-write mode:
```bash
docker compose -f docker-compose.prod.yml exec -t mariadb mariadb -uroot -p -e "
  SELECT @@global.read_only;
"
```
*(Expected Output: `0`)*

### Step 2: Scale Up Application Containers

Update the orchestration configurations to provision the active container fleet in the DR location:

```bash
# 1. Edit the env files to target local services
cp docker/env/production.env.example .env

# 2. Modify .env to point the database and redis connection strings to local DR hosts
# DB_HOST=dr-mariadb-ip
# REDIS_HOST=dr-redis-ip

# 3. Pull or compile the target application images matching the exact commit hash of the last live release
docker compose -f docker-compose.prod.yml pull

# 4. Scale up backend API, Supervisor workers, and Nginx containers
docker compose -f docker-compose.prod.yml up -d --scale backend=4 --scale nginx=2
```

Verify containers are running:
```bash
docker compose -f docker-compose.prod.yml ps
```

### Step 3: Run Post-Failover Verification & Health Check

Before directing user traffic to the DR region, verify application integrity:

```bash
# 1. Check local web server is responding
curl -fsS http://localhost/healthz

# 2. Check complete internal readiness (DB connections, S3 path, Redis)
curl -fsS http://localhost/readyz

# 3. Confirm database migration parity (ensure no pending schemas exist)
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php --pretend
```

### Step 4: Execute DNS Routing Failover

Update public DNS records to point to the DR Load Balancer.

#### Option A: AWS Route 53 (Manual / CLI)
If automatic Route 53 Failover Routing is not triggered, execute the CLI failover:

```bash
aws route53 change-resource-record-sets \
  --hosted-zone-id Z019F66BB_ZONE \
  --change-batch '{
    "Comment": "Emergency failover to DR Region 2 Load Balancer",
    "Changes": [
      {
        "Action": "UPSERT",
        "ResourceRecordSet": {
          "Name": "api.skyfinetworks.com",
          "Type": "A",
          "TTL": 60,
          "ResourceRecords": [
            {
              "Value": "54.210.12.34" 
            }
          ]
        }
      }
    ]
  }'
```
*(Replace `54.210.12.34` with the actual public elastic IP of the DR Nginx load balancer)*

#### Option B: Cloudflare API DNS Switch
```bash
curl -X PUT "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE_ID/dns_records/RECORD_ID" \
     -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
     -H "Content-Type: application/json" \
     --data '{
       "type": "A",
       "name": "api.skyfinetworks.com",
       "content": "54.210.12.34",
       "ttl": 60,
       "proxied": true
     }'
```

---

## 5.0 Phase 3: Service Validation & Warm-Up (T + 90 to T + 120 mins)

With DNS propagated, complete final verification checks to ensure high performance:

1.  **Flush and Re-warm Cache:** Clear any stale session structures and cache records.
    ```bash
    docker compose -f docker-compose.prod.yml exec redis redis-cli flushall
    ```
2.  **Verify MikroTik API Paths:** Verify that the DR backend can reach all active edge routers by testing the connectivity of router 1:
    ```bash
    docker compose -f docker-compose.prod.yml exec backend \
      php -r "
        require 'autoload.php';
        \$app = require 'config/app.php';
        \$service = \$app->get(SkyFi\Monitoring\Services\DeviceHealthPollingService::class);
        echo \$service->pollDevice(1) ? 'Router 1 Online\n' : 'Router 1 Unreachable\n';
      "
    ```
3.  **Confirm Customer Portal State:** Browse to the customer login screen and complete a test authentication check.

---

## 6.0 Phase 4: Failback Procedures (Return to Primary Site)

Failback is a planned, low-urgency maintenance activity executed only after the primary site has been declared stable for at least 24 hours.

```
[ Active DR Location ]                       [ Rebuilding Primary Location ]
 (Runs as active master)                          (Configured as replica)
  ----------------------                        -----------------------------
  * App writes are live                         * App is stopped
  * MariaDB Master ------------------------->   * MariaDB replica catches up
  * File uploads active                         * File sync catches up
```

### Step 1: Resynchronize Primary Database

Because writes occurred in the DR region, the primary database is now out of date. You must make the primary database a replica of the DR database.

1.  Take a lock-free snapshot of the DR database:
    ```bash
    docker compose -f docker-compose.prod.yml exec mariadb \
      mariadb-dump -uroot -p --single-transaction --master-data=2 skyfi > backups/dr_failback_sync.sql
    ```
2.  Transfer `dr_failback_sync.sql` to the primary server.
3.  Stop the application containers on the primary server (keep MariaDB running).
4.  Load the backup into the primary MariaDB.
5.  Extract the `CHANGE MASTER TO` coordinates from the top of `dr_failback_sync.sql` and execute them on the primary DB to catch up with any delta transactions that occurred during the dump.
6.  Wait for replica lag to reach `0`.

### Step 2: Initiate Failback Maintenance Window

1.  Place the DR region in maintenance mode (touch `storage/logs/maintenance.flag`).
2.  Wait 5 minutes for active SQL transactions to drain.
3.  Switch DNS routing back to the primary location's load balancer IP.
4.  Remove the maintenance flag on the primary server.
5.  Start primary containers and background workers.
6.  Decommission or scale down the DR container fleet back to its "pilot light" idle state.

---

## 7.0 Disaster Recovery Drills & Audits

Annual simulation drills are mandatory to ensure runbook efficiency and team preparedness.

*   **Frequency:** Once per calendar year.
*   **Rules of Engagement:** Drills must be executed in a dedicated, isolated staging environment that mimics production network structures. Actual production routing must not be touched during standard exercises.
*   **Metrics Review:** SRE team must log execution times for each runbook phase. If RTO exceeds 4 hours, a post-drill retro must identify bottlenecks and optimize script execution.
