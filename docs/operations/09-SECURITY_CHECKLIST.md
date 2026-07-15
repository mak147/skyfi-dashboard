# Operations Guide 09 — Security Hardening Checklist

**Phase:** 5 — Operations Documentation  
**Audience:** Security Officers, SRE, Network Administrators, System Auditors  
**Status:** Production-Ready Standard  
**Last reviewed:** 2026-07-15

---

## 1.0 Document Scope & Goals

The SkyFi ISP Management System stores highly sensitive personal and financial data, as well as credentials used to provision active MikroTik infrastructure. Compromising these assets poses severe financial and operational risks.

This guide provides a comprehensive security checklist that must be reviewed, audited, and signed off quarterly to ensure the platform remains secure and compliant with modern standards.

---

## 2.0 Security Auditing Checklist (Quarterly Review)

| Audit Area | Item / Task | Target Standard | Verified (Sign) |
| --- | --- | --- | --- |
| **Secrets Management** | Rotation of active JWT keys | Every 90 Days | |
| **Secrets Management** | Rotation of MariaDB & Redis passwords | Every 180 Days | |
| **Secrets Management** | Revocation of stale administrator sessions | Automatic after 30 mins | |
| **Network Security** | TLS Cipher Suite Compliance audit | TLS 1.3 / Hardened 1.2 | |
| **Network Security** | UFW / iptables Firewall rules audit | Drop all unused inbound ports | |
| **Server Security** | Host OS SSH hardening | Disable root login, enforce key auth | |
| **Server Security** | Dependency Vulnerability scanning | 0 critical CVEs on backend/frontend | |
| **Infrastructure** | MikroTik SSH/API credential encryption | AES-256 with base64-encoded key | |
| **Compliance** | Financial audit logging verification | Immutable log tracks of invoice edits | |

---

## 3.0 Secrets & Key Rotation Runbook

Do not hardcode secrets. In production, secrets are stored in the host `.env` file. Follow these steps to rotate core keys safely:

### 3.1 Step 1: Rotate the JWT Signature Key

Rotating the JWT key forces all active users (including administrators) to re-authenticate. This is necessary during security audits or if a key is compromised.

1.  Generate a high-entropy 32-byte JWT secret:
    ```bash
    openssl rand -base64 48
    ```
2.  Edit `.env` on the host:
    ```env
    JWT_SECRET="[PASTE_GENERATED_BASE64_VALUE_HERE]"
    ```
3.  Gracefully reload the backend containers to apply the change:
    ```bash
    docker compose -f docker-compose.prod.yml exec backend kill -USR2 1
    ```

### 3.2 Step 2: Rotate MikroTik Infrastructure Encryption Keys

Router credentials saved in the database are encrypted using AES-256-CBC. The encryption key is defined in `.env` as `MIKROTIK_ENCRYPTION_KEY`.

1.  Generate a 32-byte key:
    ```bash
    php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
    ```
2.  **CRITICAL:** Before updating `.env`, you must run a script to decrypt current router credentials using the old key and re-encrypt them using the new key. Failing to do so will lock the platform out of all MikroTik routers.
3.  Update `.env` with the new `MIKROTIK_ENCRYPTION_KEY` and restart the containers.

---

## 4.0 Transport Layer Security (TLS) Hardening

Do not terminate SSL directly inside the Nginx container. Use an edge proxy (e.g., AWS Application Load Balancer, Cloudflare, or Let's Encrypt Traefik host) to terminate TLS.

### 4.1 Recommended Load Balancer TLS Ciphers

Configure your TLS terminator to restrict handshakes to these secure protocols and ciphers:

```text
Protocols: TLSv1.2, TLSv1.3

Secure Cipher Suite:
ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384
```

### 4.2 Enforce HTTP Strict Transport Security (HSTS)

Ensure Nginx appends the HSTS header to prevent browsers from communicating via unencrypted HTTP:

```nginx
# Add inside /docker/nginx/default.conf server block:
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
add_header X-Frame-Options "DENY" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self' https://api.skyfinetworks.com" always;
```

---

## 5.0 Host Server SSH Hardening

SSH access to the production host must be locked down to prevent brute-force attacks.

Edit `/etc/ssh/sshd_config` on the host machine:

```text
# Disable default SSH port
Port 2222

# Disable root login
PermitRootLogin no

# Only allow public key authentication (disable passwords)
PasswordAuthentication no
PubkeyAuthentication yes

# Prevent empty password attempts
PermitEmptyPasswords no

# Log detailed connection metadata
LogLevel VERBOSE
```

Apply SSH changes:
```bash
sudo systemctl restart sshd
```

---

## 6.0 API Rate Limiting Configuration

The platform includes a built-in rate-limiting middleware to protect endpoints against brute-force attacks and abuse.

### 6.1 Default Limits

*   **Public API / Auth Endpoints:** Max 30 requests per minute per IP.
*   **Customer Portal Paths:** Max 100 requests per minute per User.
*   **Administrative Paths:** Max 500 requests per minute per IP.

### 6.2 Modifying Rate Limit Thresholds

If administrators need to scale or tighten request limits, edit `/backend/config/app.php` or the relevant environment variables:

```php
return [
    'rate_limiting' => [
        'enabled' => env('RATE_LIMIT_ENABLED', true),
        'max_attempts_auth' => env('RATE_LIMIT_MAX_ATTEMPTS_AUTH', 30),
        'decay_minutes_auth' => 1,
    ]
];
```

---

## 7.0 Static Application Dependency Audits

Regularly scan backend dependencies (Composer packages) and frontend libraries (npm packages) to identify and patch security vulnerabilities.

### 7.1 Scan PHP Dependencies (Composer)

Run this command inside the backend container to query the local security advisory database:

```bash
docker compose -f docker-compose.prod.yml exec backend \
  composer audit
```

### 7.2 Scan React Frontend Dependencies (NPM)

```bash
cd frontend && npm audit
```
*(If vulnerabilities are found, run `npm audit fix --production` to resolve them safely).*
