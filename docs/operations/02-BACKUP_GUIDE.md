# Operations Guide 02 — Backup Guide

**Phase:** 5 — Operations Documentation  
**Audience:** Site Reliability Engineers (SRE), Database Administrators (DBA), DevOps Engineers  
**Status:** Production-Ready Standard  
**Last reviewed:** 2026-07-15

---

## 1.0 Executive Overview

The SkyFi ISP Management System stores mission-critical transactional records: billing ledgers, network hardware configurations, PPPoE/Hotspot session maps, and customer data. Data loss is a critical risk to business operations. 

SkyFi enforces a strict **3-2-1 backup strategy**:
*   **3 copies of data:** Active Database State, Local Logical Snapshot, and Off-site Archive.
*   **2 different storage mediums:** High-performance SSD volume storage and cloud-native Object Storage (S3 / SFTP).
*   **1 off-site location:** Cross-region AWS S3 bucket replication or external secure storage vault.

This guide details the backup architecture, active schedules, database schemas that track backup state, and step-by-step instructions for executing automated or manual backups.

---

## 2.0 Backup Architecture & Schema

SkyFi includes a native `Backup` module that manages, schedules, and registers backups. Backups are tracked in the MariaDB database to provide full visibility through the administrative panel.

```
       [ Backup Cron/Supervisor ]
                   |
                   v
       [ BackupScheduler Service ]
                   |
       +-----------+-----------+
       |                       |
(Executes DB Dump)       (Compresses uploads)
       |                       |
       v                       v
 [ Local Backup File ]   [ Local Tarball ]
       \                       /
        \                     /
         v                   v
      [ Encrypt & Compute SHA-256 ]
                   |
                   v
      [ Upload to Storage Provider ]
       (Local NAS / AWS S3 / SFTP)
                   |
     +-------------+-------------+
     |                           |
     v                           v
[ Update backup_files ]   [ Log status to backup_jobs ]
```

### 2.1 Schema Tables

The backup state is tracked in the following tables:
1.  **`backup_storage_providers`**: Defines the physical storage targets (e.g., AWS S3, local SFTP NAS, local disk).
2.  **`backup_schedules`**: Configures automated Cron schedules for different types of backups (`database`, `files`, `config`, `full`).
3.  **`backup_jobs`**: Logs individual executions of backup jobs, including execution times, statuses (`running`, `completed`, `failed`), and error logs.
4.  **`backup_files`**: Stores metadata for files written by successful backup jobs, including their exact storage path, file size, SHA-256 checksum, and expiration timestamp.
5.  **`verification_history`**: Records the results of regular integrity audits (checks if files exist and if the checksum is correct).

---

## 3.0 Automated Backup Schedules & Retention

Our production environment enforces the following retention matrices:

| Target Component | Backup Type | Frequency / Cron | Local Retention | Cloud/DR Retention | Target Storage Provider |
| --- | --- | --- | --- | --- | --- |
| **MariaDB Database** | Database Dump | Every 6 hours (`0 */6 * * *`) | 7 Days | 90 Days | S3/SFTP Default Provider |
| **Uploaded Documents**| Tarball / Zip | Daily (`0 2 * * *`) | 7 Days | 180 Days | S3 Cloud Provider |
| **System Configurations**| JSON Config | Daily (`30 2 * * *`) | 30 Days | 365 Days | Local NAS Provider |
| **Complete System** | Full Snapshot | Weekly (`0 3 * * 0`) | 14 Days | 365 Days | S3 Cloud Provider |

---

## 4.0 Step-by-Step Manual Backup Execution

There are situations (e.g., prior to major system upgrades or network topology re-wiring) where a manual, on-demand backup must be executed immediately.

### 4.1 On-Demand Database Backup via Host CLI

To execute a complete logical backup of the MariaDB database using container execution:

```bash
# 1. Create a timestamped backup directory
mkdir -p backups/mariadb

# 2. Extract database password from .env
DB_ROOT_PASSWORD=$(grep MARIADB_ROOT_PASSWORD .env | cut -d '=' -f2)

# 3. Execute logical dump
docker compose -f docker-compose.prod.yml exec -t mariadb \
  mariadb-dump \
    -uroot \
    -p"${DB_ROOT_PASSWORD}" \
    --single-transaction \
    --routines \
    --triggers \
    --add-drop-table \
    skyfi > backups/mariadb/skyfi_manual_$(date +%Y%m%d_%H%M%S).sql

# 4. Verify dump file is non-empty and starts with standard SQL headers
head -n 15 backups/mariadb/skyfi_manual_*.sql
```

### 4.2 On-Demand Media / Storage Backup

To backup user-uploaded documents, invoices, contract attachments, and field site photos stored in `backend/storage/uploads`:

```bash
# 1. Create backup directory
mkdir -p backups/files

# 2. Compress the uploads directory
docker compose -f docker-compose.prod.yml exec -t backend \
  tar -czf /tmp/uploads_manual_$(date +%Y%m%d_%H%M%S).tar.gz -C storage/uploads .

# 3. Copy the archive from the container to host backup storage
docker compose -f docker-compose.prod.yml cp \
  backend:/tmp/uploads_manual_$(date +%Y%m%d_%H%M%S).tar.gz backups/files/

# 4. Clean up temporary files inside container
docker compose -f docker-compose.prod.yml exec -t backend \
  rm /tmp/uploads_manual_*.tar.gz
```

### 4.3 Triggering Backups via PHP CLI Orchestrator

Rather than running raw shell scripts, operators can trigger SkyFi's native backend orchestrator, which automatically computes checksums, registers records in `backup_jobs`, and uploads them to the default storage provider:

```bash
docker compose -f docker-compose.prod.yml exec -t backend \
  php -r "
    require 'autoload.php';
    \$app = require 'config/app.php';
    \$backupService = \$app->get(SkyFi\Backup\Services\BackupService::class);
    \$job = \$backupService->runBackup('database');
    echo 'Backup complete. Job ID: ' . \$job->id . ' Status: ' . \$job->status . PHP_EOL;
    if (\$job->status === 'failed') {
        echo 'Error: ' . \$job->errorMessage . PHP_EOL;
        exit(1);
    }
  "
```

---

## 5.0 Database Backup Verification & Integrity Audits

An untested backup is not a backup. The system includes a background worker designed to verify the physical existence and checksum accuracy of all recorded backup files.

To run an automated verification audit across all backup files:

```bash
docker compose -f docker-compose.prod.yml exec -t backend \
  php -r "
    require 'autoload.php';
    \$app = require 'config/app.php';
    \$pdo = \$app->get(PDO::class);
    
    // Fetch unverified or old verified files
    \$stmt = \$pdo->query('SELECT * FROM backup_files ORDER BY id DESC LIMIT 10');
    while(\$file = \$stmt->fetch(PDO::FETCH_ASSOC)) {
        \$exists = file_exists(\$file['file_path']);
        \$checksumMatches = false;
        if (\$exists) {
            \$calculated = hash_file('sha256', \$file['file_path']);
            \$checksumMatches = (\$calculated === \$file['checksum']);
        }
        
        \$status = (\$exists && \$checksumMatches) ? 'success' : 'failure';
        \$details = \$exists 
            ? 'Checksum match: ' . (\$checksumMatches ? 'OK' : 'MISMATCH') 
            : 'File missing from storage path';
            
        echo 'File ID ' . \$file['id'] . ': ' . \$status . ' (' . \$details . ')' . PHP_EOL;
        
        // Log result into verification_history
        \$vStmt = \$pdo->prepare('INSERT INTO verification_history (backup_file_id, status, details) VALUES (:id, :status, :details)');
        \$vStmt->execute([
            'id' => \$file['id'],
            'status' => \$status,
            'details' => \$details
        ]);
        
        if (\$status === 'success') {
            \$uStmt = \$pdo->prepare('UPDATE backup_files SET verified_at = CURRENT_TIMESTAMP WHERE id = :id');
            \$uStmt->execute(['id' => \$file['id']]);
        }
    }
  "
```

---

## 6.0 Securing Backup Data

To protect sensitive customer billing, financial transactions, and network access keys:
1.  **Encryption-at-Rest:** All S3 backup buckets must have Default KMS Server-Side Encryption (SSE-KMS) enabled with AWS Key Management Service.
2.  **SFTP / NAS Hardening:** SFTP storage providers must utilize dedicated service keys, with interactive shell execution strictly disabled for the backup user.
3.  **Local Backup Hardening:** Local directory mounts on the production host must be root-owned with restricted permissions:
    ```bash
    chmod 700 /home/user/skyfi-dashboard/backups
    chmod 600 /home/user/skyfi-dashboard/backups/*
    ```
4.  **Database Credential Isolation:** Database backup tasks must execute under a limited database user with readonly privileges on user/financial tables, unless performing structural schema backups.
