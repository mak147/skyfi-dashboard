# Operations Guide 03 — Restore Procedures

**Phase:** 5 — Operations Documentation  
**Audience:** Site Reliability Engineers (SRE), Database Administrators (DBA), System Administrators  
**Status:** Production-Ready Standard  
**Last reviewed:** 2026-07-15

---

## 1.0 Executive Overview & Safety Protocols

Restoring data is a high-risk operation. Improper restoration can lead to permanent data corruption, out-of-sync financial ledgers, broken relational keys, or prolonged system outages.

### 1.1 The Golden Rules of Restoration

1.  **Never Restore Directly to Production Without a Fresh Snapshot:** Always take an immediate, manual backup of the active database *before* performing any restore actions, regardless of how minor they may seem.
2.  **Enable Maintenance Mode:** Ensure the application is placed in maintenance mode to block user interaction and background tasks. This prevents write-skew where new transactions occur during a database rewrite.
3.  **Restore to a Sandbox First:** Unless facing a complete catastrophic outage, restore the backup file into a temporary development/staging environment first to verify integrity and correctness.
4.  **Confirm the Checksum:** Always calculate the SHA-256 hash of the target backup file on the host machine and verify it matches the hash registered in the `backup_files` table.

---

## 2.0 Placing the System in Maintenance Mode

Before starting any database restoration, block incoming traffic and suspend active workers:

### 2.1 Block API Traffic with Nginx Maintenance Flag

1.  Create a maintenance trigger file in the web root:
    ```bash
    docker compose -f docker-compose.prod.yml exec backend touch storage/logs/maintenance.flag
    ```
2.  Our Nginx container is configured to check for this flag and return an HTTP `503 Service Unavailable` with a user-friendly JSON/HTML payload for all API and App paths.
3.  Verify that external requests now receive a `503` status.

### 2.2 Suspend Background Worker Processes

To prevent active queues from processing during the restore:

```bash
docker compose -f docker-compose.prod.yml exec supervisor supervisorctl stop all
```

Verify that all workers (PPPoE sync, billing checkers, SNMP pollers) are stopped:
```bash
docker compose -f docker-compose.prod.yml exec supervisor supervisorctl status
```

---

## 3.0 Database Restoration Procedures

Depending on the scenario, you will either restore the entire database (catastrophic failure) or restore to a side-car instance to recover a single record.

### 3.1 Scenario A: Complete Database Reconstruction

Use this procedure if the production database has suffered catastrophic corruption or a cluster volume failure.

```bash
# 1. Identify the target backup SQL file
BACKUP_FILE="backups/mariadb/skyfi_manual_20260715_120000.sql"

# 2. Verify the SHA-256 checksum
echo "Verifying checksum..."
CALCULATED_HASH=$(sha256sum "$BACKUP_FILE" | cut -d ' ' -f1)
echo "Local Checksum: $CALCULATED_HASH"

# Compare this hash with the corresponding record in `backup_files.checksum`

# 3. Pull MariaDB root password
DB_ROOT_PASSWORD=$(grep MARIADB_ROOT_PASSWORD .env | cut -d '=' -f2)

# 4. Import the SQL file into the running container
# This drops tables and recreates them based on the drop-table headers in our backups
echo "Importing backup..."
docker compose -f docker-compose.prod.yml exec -T mariadb \
  mariadb -uroot -p"${DB_ROOT_PASSWORD}" skyfi < "$BACKUP_FILE"

# 5. Run Database Migrations to apply any incremental changes since backup creation
echo "Running migrations..."
docker compose -f docker-compose.prod.yml exec backend php database/migrate.php

# 6. Re-warm Cache
echo "Clearing system cache..."
docker compose -f docker-compose.prod.yml exec redis redis-cli flushall
```

### 3.2 Scenario B: Point-in-Time Single Record Recovery (Side-car Restore)

Use this procedure when a user accidentally deletes critical customer or invoice records, and you need to retrieve *only those specific records* without overwriting modern transactions.

```bash
# 1. Start a temporary, isolated MariaDB container on a separate port
docker run --name skyfi-restore-temp \
  -e MARIADB_DATABASE=skyfi_temp \
  -e MARIADB_ROOT_PASSWORD=temporary-restore-password \
  -p 3307:3306 \
  -d mariadb:11.4

# 2. Wait 10 seconds for the database to boot up, then load the backup file
docker exec -i skyfi-restore-temp mariadb -uroot -ptemporary-restore-password skyfi_temp < backups/mariadb/skyfi_manual_20260715_120000.sql

# 3. Connect to the temporary database and find the deleted record
# Example: Recovering deleted customer ID 402
docker exec -it skyfi-restore-temp mariadb -uroot -ptemporary-restore-password -e "
  SELECT * FROM skyfi_temp.customers WHERE id = 402;
"

# 4. Generate an INSERT SQL script containing the missing record
docker exec -i skyfi-restore-temp mysqldump \
  -uroot \
  -ptemporary-restore-password \
  --no-create-info \
  --where="id = 402" \
  skyfi_temp customers > backups/recovered_customer_402.sql

# 5. Review and import the recovered record into the production DB
docker compose -f docker-compose.prod.yml exec -T mariadb \
  mariadb -uroot -p"${DB_ROOT_PASSWORD}" skyfi < backups/recovered_customer_402.sql

# 6. Clean up temporary container
docker stop skyfi-restore-temp
docker rm skyfi-restore-temp
rm backups/recovered_customer_402.sql
```

---

## 4.0 Media & File Restoration Procedures

If attachment folders in `backend/storage/uploads` are deleted or corrupted, follow this procedure to restore physical assets:

```bash
# 1. Identify the target tarball file
BACKUP_ARCHIVE="backups/files/uploads_manual_20260715_120000.tar.gz"

# 2. Extract files into a temporary directory inside the container
docker compose -f docker-compose.prod.yml cp "$BACKUP_ARCHIVE" backend:/tmp/restore_uploads.tar.gz

# 3. Wipe current uploads folder (only after backup is verified!)
docker compose -f docker-compose.prod.yml exec backend rm -rf storage/uploads/*

# 4. Extract archive inside the storage directory
docker compose -f docker-compose.prod.yml exec backend \
  tar -xzf /tmp/restore_uploads.tar.gz -C storage/uploads/

# 5. Fix permissions (Nginx / PHP-FPM runs as www-data in production)
docker compose -f docker-compose.prod.yml exec backend \
  chown -R www-data:www-data storage/uploads

docker compose -f docker-compose.prod.yml exec backend \
  chmod -R 775 storage/uploads

# 6. Clean up temporary file inside container
docker compose -f docker-compose.prod.yml exec backend rm /tmp/restore_uploads.tar.gz
```

---

## 5.0 Restoring via Application-Level RestoreService

The platform also provides programmatic restoration tracking via its built-in API. An admin with `backup.restore` permissions can trigger the restore. This logs execution metadata to the `restore_history` table.

To run the programmatic restorer:

```bash
docker compose -f docker-compose.prod.yml exec -t backend \
  php -r "
    require 'autoload.php';
    \$app = require 'config/app.php';
    \$restoreService = \$app->get(SkyFi\Backup\Services\RestoreService::class);
    
    // File ID 15 in backup_files table
    \$fileId = 15; 
    
    echo 'Initiating programmatic restoration for file ID ' . \$fileId . '...' . PHP_EOL;
    try {
        \$restoreId = \$restoreService->initiateRestore(\$fileId, 'production');
        echo 'Restore completed successfully. Log ID: ' . \$restoreId . PHP_EOL;
    } catch (Exception \$e) {
        echo 'Restore FAILED: ' . \$e->getMessage() . PHP_EOL;
        exit(1);
    }
  "
```

---

## 6.0 Post-Restoration Validation Protocol

Do not open the system to public traffic until you have performed the following validation checks:

1.  **Check Table Counts:** Ensure restored table row counts align with historical totals.
    ```sql
    SELECT table_name, table_rows FROM information_schema.tables WHERE table_schema = 'skyfi';
    ```
2.  **Verify API Readiness Endpoint:**
    ```bash
    curl -fsS http://localhost/readyz
    ```
3.  **Perform Functional Smoke Test:**
    *   Verify admin login.
    *   Load a customer billing profile and ensure invoices load successfully.
    *   Check MikroTik router online status in the Monitoring Dashboard.
4.  **Remove Maintenance Flag:**
    ```bash
    docker compose -f docker-compose.prod.yml exec backend rm storage/logs/maintenance.flag
    ```
5.  **Restart Supervisor Workers:**
    ```bash
    docker compose -f docker-compose.prod.yml exec supervisor supervisorctl start all
    ```
6.  **Confirm Status:** Verify that external traffic is flowing and that background queues are active.
