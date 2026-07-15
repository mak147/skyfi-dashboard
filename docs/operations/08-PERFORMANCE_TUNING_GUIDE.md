# Operations Guide 08 — Performance Tuning Guide

**Phase:** 5 — Operations Documentation  
**Audience:** Site Reliability Engineers (SRE), Database Administrators (DBA), Systems Architects  
**Status:** Production-Ready Standard  
**Last reviewed:** 2026-07-15

---

## 1.0 Document Scope & Tuning Strategy

The SkyFi ISP Management System is designed to scale dynamically as subscriber counts grow. However, default database, runtime, and web server configurations are optimized for minimal resource footprints and will fail under high enterprise loads.

This guide provides concrete, mathematically justified, and production-tested configuration parameters for:
*   **PHP-FPM Worker Pools:** Sizing processes according to physical RAM limits.
*   **MariaDB InnoDB Database Engine:** Optimizing cache allocations, logs, and disk flush rates.
*   **Redis Cache Engine:** Eviction policies and memory caps.
*   **Nginx Web Server:** High-concurrency connection handling and gzip compression.

---

## 2.0 PHP-FPM Process Pool Optimization

A poorly configured PHP-FPM pool can cause memory exhaustion (too many workers) or 502/504 errors (not enough workers).

### 2.1 Worker Pool Sizing Formulas

The number of maximum concurrent PHP-FPM workers (`pm.max_children`) is calculated using the formula:

$$\text{pm.max\_children} = \frac{\text{Total Available Server RAM} - \text{System Overhead Reserved Memory}}{\text{Average Memory Footprint per PHP Process}}$$

#### Parameter Specifications:
*   **Total Available Server RAM:** The physical RAM of the VM (e.g., 8 GB).
*   **System Overhead Reserved Memory:** Memory set aside for the OS, MariaDB, Redis, and Nginx (e.g., 3 GB).
*   **Average Memory Footprint per PHP Process:** Under the SkyFi framework, an average API request consumes between **60 MB and 80 MB** of RAM.

#### Example Sizing (On an 8 GB Server hosting the entire stack):
*   Memory available for PHP-FPM: $8192\text{ MB} - 3072\text{ MB} = 5120\text{ MB}$.
*   $5120\text{ MB} / 80\text{ MB} \approx 64$ workers.

### 2.2 PHP-FPM Production Configuration

Update the `/docker/php/www.conf` pool configuration file with these values:

```text
[www]
user = www-data
group = www-data
listen = 127.0.0.1:9000

; Dynamic process management for scaling
pm = dynamic
pm.max_children = 64
pm.start_servers = 15
pm.min_spare_servers = 10
pm.max_spare_servers = 25
pm.max_requests = 1000

; Prevent slow third-party API connections from locking up workers
request_terminate_timeout = 60s
```

---

## 3.0 MariaDB InnoDB Database Engine Hardening

MariaDB is the persistent state engine for SkyFi. Optimizing its resource allocation prevents query execution queues.

### 3.1 InnoDB Buffer Pool Sizing

The `innodb_buffer_pool_size` is the single most critical parameter for database performance. It determines how much database data and indexes are cached in RAM.

*   **Dedicated Database Server:** Set to **70% to 80%** of total server RAM.
*   **Shared/Co-located Stack (Standard Compose):** Set to **30% to 40%** of total server RAM (e.g., **2G** to **3G** on an 8 GB instance).

### 3.2 Optimized MariaDB Configuration (`/etc/mysql/my.cnf`)

Apply the following performance blocks to the database configuration:

```ini
[mysqld]
# Buffer Pool allocations
innodb_buffer_pool_size = 2G
innodb_buffer_pool_instances = 2  ; Reduces internal thread locking

# Log write performance
innodb_log_file_size = 512M
innodb_log_buffer_size = 16M

# Transaction Commit Durability vs Performance
# 1 = Full ACID compliance. Safest. Disk writes sync on every transaction.
# 2 = Writes log on commit, flushes disk every second. Up to 10x faster write performance.
# In production, we accept '2' for high-frequency SNMP write workloads, relying on backup UPS/RAID.
innodb_flush_log_at_trx_commit = 2

# Connection limits
max_connections = 250
thread_cache_size = 32

# Query Cache (Disabled in modern MariaDB as it harms high-concurrency systems)
query_cache_type = 0
query_cache_size = 0
```

---

## 4.0 Redis High-Throughput Memory & Eviction Tuning

Redis handles session state, API rate limits, and short-term monitoring caches.

### 4.1 Eviction Policy Configuration

Because rate limiting data changes rapidly, we must configure an eviction policy that drops old caching values instead of throwing "OOM: Out of Memory" errors when the memory limit is reached.

Update `/etc/redis/redis.conf` or the container env variables:

```text
# Limit Redis memory footprint to 1 GB
maxmemory 1gb

# Eviction strategy: Least Recently Used (LRU) across keys with expiration sets
maxmemory-policy volatile-lru
```

### 4.2 Append Only File (AOF) Persistence

To prevent data loss of active user portal sessions while keeping high write performance, combine RDB snapshots with AOF:

```text
appendonly yes
appendfsync everysec
```

---

## 5.0 Nginx Concurrency & Web Server Tuning

Nginx sits at the edge of the SkyFi stack, managing SSL terminations and proxying API traffic.

### 5.1 High Concurrency Settings

Configure the main `/docker/nginx/default.conf` file:

```nginx
user nginx;
worker_processes auto; # Automatically matches CPU core count
worker_rlimit_nofile 65535;

events {
    worker_connections 8192; # Max concurrent TCP handles
    use epoll;
    multi_accept on;
}

http {
    include       /etc/nginx/mime.types;
    default_type  application/octet-stream;

    # Performance buffers
    sendfile        on;
    tcp_nopush      on;
    tcp_nodelay     on;
    keepalive_timeout  65;
    types_hash_max_size 2048;

    # Gzip Compression to optimize page load speeds
    gzip on;
    gzip_disable "msie6";
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
}
```

---

## 6.0 Database Index Auditing & Optimization Rules

To prevent database slow downs as customer and payment transaction tables grow:

### 6.1 Avoid SELECT *
Ensure all custom database queries explicitly fetch target columns to minimize memory consumption and database bandwidth:
```sql
-- POOR PRACTICE
SELECT * FROM billing_invoices WHERE status = 'unpaid';

-- ENTERPRISE STANDARD
SELECT id, customer_id, amount, due_date FROM billing_invoices WHERE status = 'unpaid';
```

### 6.2 Index Compound Queries
If queries consistently filter by multiple columns, create a compound index. For example, if you frequently query payments by both `customer_id` and `payment_status`:
```sql
CREATE INDEX idx_payments_cust_status ON payments (customer_id, payment_status);
```

### 6.3 Use EXPLAIN to Audit Queries
Before committing custom reporting SQL queries, run them through the database engine with the `EXPLAIN` keyword to verify that they utilize existing indexes instead of performing full table scans:
```sql
EXPLAIN SELECT id FROM customers WHERE email = 'john@example.com';
```
*(Verify that the `type` column does not read `ALL`, and `key` shows the target index name).*
