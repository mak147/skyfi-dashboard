# SkyFi ISP Management System — Production Deployment Checklist v1.0.0

**Purpose:** Pre-deployment verification checklist for promoting SkyFi v1.0.0 to production.  
**Instructions:** Complete every item before deploying. Do not deploy until all items are checked.  
**References:** `docs/deployment/DEPLOYMENT_GUIDE.md`, `docs/operations/09-SECURITY_CHECKLIST.md`, `.env.example`

---

## 1. Infrastructure readiness

| # | Item | Verified | Notes |
| --- | --- | --- | --- |
| 1.1 | Target server meets minimum requirements (2 CPU cores, 4 GB RAM) | ☐ | |
| 1.2 | Docker Engine 25+ installed on target host | ☐ | |
| 1.3 | Docker Compose plugin 2.24+ installed on target host | ☐ | |
| 1.4 | DNS records configured for frontend/API host | ☐ | |
| 1.5 | TLS terminator configured (load balancer, Cloudflare, or Let's Encrypt) | ☐ | |
| 1.6 | Firewall rules: only ports 80/443 exposed externally | ☐ | |
| 1.7 | SSH hardening applied (key auth only, root login disabled) | ☐ | |
| 1.8 | Sufficient disk space for MariaDB volumes, Redis persistence, and logs | ☐ | |

## 2. Secrets and configuration

| # | Item | Verified | Notes |
| --- | --- | --- | --- |
| 2.1 | `.env` file created from `docker/env/production.env.example` | ☐ | |
| 2.2 | `JWT_SECRET` set to at least 32 random bytes (NOT a placeholder) | ☐ | Generated: `openssl rand -base64 48` |
| 2.3 | `MIKROTIK_CREDENTIAL_ENCRYPTION_KEY` set to a valid 32-byte base64 key | ☐ | Generated: `php -r "echo base64_encode(random_bytes(32));"` |
| 2.4 | `MARIADB_ROOT_PASSWORD` set to a strong, unique password | ☐ | |
| 2.5 | `MARIADB_PASSWORD` set to a strong, unique password | ☐ | |
| 2.6 | `REDIS_PASSWORD` set to a strong, unique password | ☐ | |
| 2.7 | `APP_URL` set to the public API URL (https://) | ☐ | |
| 2.8 | `APP_ISSUER` set to match `APP_URL` | ☐ | |
| 2.9 | `APP_AUDIENCE` set to the public frontend URL (https://) | ☐ | |
| 2.10 | `CORS_ALLOWED_ORIGINS` set to exact frontend origin (https://) | ☐ | |
| 2.11 | `REFRESH_COOKIE_SECURE=true` | ☐ | Must be `true` in production |
| 2.12 | `APP_DEBUG=false` | ☐ | Must be `false` in production |
| 2.13 | `EXPOSE_PASSWORD_RESET_TOKEN` is NOT set or is `false` | ☐ | Must NOT be `true` in production |
| 2.14 | PHP-FPM sizing tuned for server (`PHP_FPM_MAX_CHILDREN`, etc.) | ☐ | Default: 40 children for 4GB RAM |
| 2.15 | MariaDB buffer pool sized appropriately (`MARIADB_INNODB_BUFFER_POOL_SIZE`) | ☐ | Default: 1G |
| 2.16 | `.env` file is NOT committed to version control | ☐ | Must be in `.gitignore` |
| 2.17 | `.env` file has restrictive file permissions (600 or 400) | ☐ | |

## 3. Application deployment

| # | Item | Verified | Notes |
| --- | --- | --- | --- |
| 3.1 | Docker images built at the correct version tag (`v1.0.0`) | ☐ | |
| 3.2 | All containers start successfully (`docker compose ps`) | ☐ | |
| 3.3 | MariaDB health check passing | ☐ | |
| 3.4 | Redis health check passing | ☐ | |
| 3.5 | Backend health check passing | ☐ | |
| 3.6 | Nginx health check passing | ☐ | |
| 3.7 | Database migrations applied successfully | ☐ | `php database/migrate.php` |
| 3.8 | `--pretend` migration run confirmed no unexpected migrations | ☐ | |
| 3.9 | RBAC seed data applied | ☐ | `php database/seed.php` |
| 3.10 | Initial administrator created with secure credentials | ☐ | Via `SEED_ADMIN_EMAIL`/`SEED_ADMIN_PASSWORD` |
| 3.11 | `/healthz` returns `{"status":"ok"}` | ☐ | |
| 3.12 | `/readyz` returns `{"status":"ready"}` | ☐ | |

## 4. Functional verification (smoke tests)

| # | Item | Verified | Notes |
| --- | --- | --- | --- |
| 4.1 | Administrator login works via frontend | ☐ | |
| 4.2 | Dashboard loads with KPI data | ☐ | |
| 4.3 | Customer CRUD works (create, read, update, delete) | ☐ | |
| 4.4 | Invoice creation and status transition works | ☐ | |
| 4.5 | Payment receipt and allocation works | ☐ | |
| 4.6 | Finance dashboard loads with revenue/expense data | ☐ | |
| 4.7 | RBAC: Non-admin user receives 403 on unauthorized actions | ☐ | |
| 4.8 | Customer portal login and self-service works | ☐ | |
| 4.9 | Workflow builder page loads | ☐ | |
| 4.10 | Notification inbox is visible | ☐ | |

## 5. Security verification

| # | Item | Verified | Notes |
| --- | --- | --- | --- |
| 5.1 | TLS termination is active (no HTTP-only traffic to application) | ☐ | |
| 5.2 | Security headers present in responses (`X-Content-Type-Options`, `X-Frame-Options`, etc.) | ☐ | |
| 5.3 | CORS headers only include configured origins | ☐ | |
| 5.4 | No stack traces or debug info in error responses | ☐ | |
| 5.5 | Rate limiting active on login endpoint (verify 429 after repeated attempts) | ☐ | |
| 5.6 | JWT tokens expire correctly (wait for access token TTL) | ☐ | |
| 5.7 | Refresh cookie is HttpOnly, Secure, SameSite=Strict | ☐ | |
| 5.8 | Database accessible only from within Docker network (no external port exposure) | ☐ | |
| 5.9 | Redis accessible only from within Docker network (no external port exposure) | ☐ | |
| 5.10 | MikroTik credentials are stored as ciphertext (check DB) | ☐ | |
| 5.11 | Dependency audit clean (`composer audit`, `npm audit`) | ☐ | |

## 6. Backup and recovery

| # | Item | Verified | Notes |
| --- | --- | --- | --- |
| 6.1 | Database backup script tested | ☐ | |
| 6.2 | Backup restoration tested on separate instance | ☐ | |
| 6.3 | Off-site backup destination configured | ☐ | |
| 6.4 | Backup schedule configured (cron or external scheduler) | ☐ | |
| 6.5 | Redis persistence verified (AOF enabled) | ☐ | |
| 6.6 | Persistent volumes identified and documented | ☐ | `mariadb-data`, `redis-data`, `backend-storage` |

## 7. Monitoring and alerting

| # | Item | Verified | Notes |
| --- | --- | --- | --- |
| 7.1 | Health endpoint monitored by external service (uptime check) | ☐ | |
| 7.2 | Readiness endpoint monitored by load balancer | ☐ | |
| 7.3 | Log aggregation configured (or planned) | ☐ | |
| 7.4 | Alert rules configured for critical services (MariaDB down, Redis down, backend 5xx) | ☐ | |
| 7.5 | On-call rotation or escalation path defined | ☐ | |

## 8. Documentation

| # | Item | Verified | Notes |
| --- | --- | --- | --- |
| 8.1 | Deployment guide accessible to operations team | ☐ | `docs/deployment/DEPLOYMENT_GUIDE.md` |
| 8.2 | Operations runbooks accessible | ☐ | `docs/operations/` |
| 8.3 | Security checklist reviewed and signed off | ☐ | `docs/operations/09-SECURITY_CHECKLIST.md` |
| 8.4 | Incident response plan reviewed by team | ☐ | `docs/operations/05-INCIDENT_RESPONSE.md` |
| 8.5 | Disaster recovery runbook reviewed by team | ☐ | `docs/operations/04-DISASTER_RECOVERY_RUNBOOK.md` |
| 8.6 | Troubleshooting guide accessible | ☐ | `docs/operations/10-TROUBLESHOOTING_GUIDE.md` |

## 9. Rollback readiness

| # | Item | Verified | Notes |
| --- | --- | --- | --- |
| 9.1 | Previous database backup available | ☐ | |
| 9.2 | Rollback procedure documented and tested | ☐ | See `UPGRADE_GUIDE.md` |
| 9.3 | Previous Docker images available (if applicable) | ☐ | |
| 9.4 | Rollback decision criteria defined | ☐ | E.g., >5% error rate, health check failure |

---

## Final sign-off

| Role | Name | Date | Signature |
| --- | --- | --- | --- |
| DevOps / Infrastructure | | | |
| Security Officer | | | |
| Release Manager | | | |
| Product Owner | | | |

**Production deployment is authorized only after all items above are verified and signed off.**
