# SkyFi ISP Management System — Acceptance Checklist v1.0.0

**Purpose:** Verify that the SkyFi v1.0.0 release meets all functional and non-functional acceptance criteria before production deployment.  
**Instructions:** Complete each item and sign off. All items must pass for the release to be accepted.

---

## 1. Functional acceptance

### 1.1 Authentication & Authorization

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.1.1 | Login with valid credentials | JWT access token returned; refresh cookie set | ☐ | |
| 1.1.2 | Login with invalid credentials | 401 response with structured error | ☐ | |
| 1.1.3 | Access protected endpoint without token | 401 response | ☐ | |
| 1.1.4 | Access protected endpoint with valid token | 200 response with data | ☐ | |
| 1.1.5 | Refresh token rotation | New refresh cookie set; old token invalidated | ☐ | |
| 1.1.6 | Logout | Refresh cookie cleared; token invalidated | ☐ | |
| 1.1.7 | Forgot password | Email/reset flow completes; no token in response | ☐ | |
| 1.1.8 | Reset password | Password updated; can login with new password | ☐ | |
| 1.1.9 | Change password (authenticated) | Password updated; old token still works until expiry | ☐ | |
| 1.1.10 | Rate limiting on login | Excessive attempts blocked (429) | ☐ | |
| 1.1.11 | RBAC: View permissions | `GET /api/v1/me/permissions` returns user's effective permissions | ☐ | |
| 1.1.12 | RBAC: Permission enforcement | User without required permission receives 403 | ☐ | |
| 1.1.13 | RBAC: Super-admin wildcard | Super-admin can access all endpoints | ☐ | |

### 1.2 Customer Management

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.2.1 | Create customer | Customer created with `lead` status | ☐ | |
| 1.2.2 | List customers | Paginated list returned (max 100 per page) | ☐ | |
| 1.2.3 | View customer detail | Full customer data returned | ☐ | |
| 1.2.4 | Update customer | Customer data updated | ☐ | |
| 1.2.5 | Transition customer status (lead → prospect) | Status updated successfully | ☐ | |
| 1.2.6 | Invalid status transition (lead → disconnected) | Validation error returned | ☐ | |
| 1.2.7 | Soft-delete customer | Customer marked deleted; not in default list | ☐ | |

### 1.3 Billing

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.3.1 | Create draft invoice | Invoice created with `draft` status | ☐ | |
| 1.3.2 | Add invoice items | Items added; subtotal and tax calculated | ☐ | |
| 1.3.3 | Transition invoice (draft → pending) | Status updated | ☐ | |
| 1.3.4 | Transition invoice (pending → issued) | Status updated | ☐ | |
| 1.3.5 | Invalid transition (draft → paid) | Validation error returned | ☐ | |
| 1.3.6 | Immutable invoice (paid status) | Cannot update a finalized invoice | ☐ | |
| 1.3.7 | Generate invoice from package | Invoice auto-populated from package pricing | ☐ | |
| 1.3.8 | Bulk generate invoices | Multiple invoices generated | ☐ | |

### 1.4 Payments

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.4.1 | Create payment | Payment recorded | ☐ | |
| 1.4.2 | Allocate payment to invoice | Allocation created; invoice balance reduced | ☐ | |
| 1.4.3 | Refund payment | Refund recorded; allocation reversed | ☐ | |
| 1.4.4 | Export receipt as PDF | PDF file downloaded | ☐ | |

### 1.5 Finance

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.5.1 | View chart of accounts | COA list returned | ☐ | |
| 1.5.2 | Create journal entry | Double-entry journal created | ☐ | |
| 1.5.3 | View ledger | Ledger entries with running balance | ☐ | |
| 1.5.4 | Finance dashboard | Revenue, expenses, operating margin displayed | ☐ | |

### 1.6 Network Provisioning

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.6.1 | Register MikroTik router | Router created with encrypted credentials | ☐ | |
| 1.6.2 | Test router connection | Connectivity test returns success/failure | ☐ | |
| 1.6.3 | Create PPPoE account | Account provisioned; secret pushed to router (if connected) | ☐ | |
| 1.6.4 | Create hotspot user | User created; synced to router | ☐ | |
| 1.6.5 | Generate hotspot vouchers | Batch voucher created with print formatting | ☐ | |

### 1.7 Infrastructure & Monitoring

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.7.1 | Create POP site, tower, sector, device | Hierarchy created with FK relationships | ☐ | |
| 1.7.2 | View device status | Current status and interface data returned | ☐ | |
| 1.7.3 | Create alert | Alert created and visible in monitoring dashboard | ☐ | |

### 1.8 Support

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.8.1 | Create support ticket | Ticket created with `open` status | ☐ | |
| 1.8.2 | Assign ticket | Ticket assigned to team/agent | ☐ | |
| 1.8.3 | Add comment | Comment added to ticket timeline | ☐ | |
| 1.8.4 | Close ticket | Status changed to `closed` | ☐ | |

### 1.9 Inventory, Purchasing, Vendors

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.9.1 | Create product and warehouse | Product and warehouse created | ☐ | |
| 1.9.2 | Create purchase request | PR created in `draft` status | ☐ | |
| 1.9.3 | Approve and convert PR to PO | PO created from approved PR | ☐ | |
| 1.9.4 | Create vendor with contact | Vendor and contact created | ☐ | |

### 1.10 Field Service

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.10.1 | Create technician | Technician profile created | ☐ | |
| 1.10.2 | Create installation request | Request created and visible on dashboard | ☐ | |
| 1.10.3 | Create work order | Work order linked to request/technician | ☐ | |

### 1.11 System & Notifications

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.11.1 | View company profile | Profile data returned | ☐ | |
| 1.11.2 | Create branch | Branch created | ☐ | |
| 1.11.3 | View notification inbox | Unread notifications listed | ☐ | |
| 1.11.4 | Mark notification as read | Notification marked as read | ☐ | |

### 1.12 Audit, Backup, Integration, Workflow

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.12.1 | View audit logs | Audit entries with actor/resource/timestamp | ☐ | |
| 1.12.2 | Create backup schedule | Schedule created with next-run time | ☐ | |
| 1.12.3 | Create API key | Key generated with specified scopes | ☐ | |
| 1.12.4 | Create webhook | Webhook registered with target URL | ☐ | |
| 1.12.5 | Create workflow | Workflow created with trigger/condition/action | ☐ | |
| 1.12.6 | Execute workflow manually | Execution starts; status tracked | ☐ | |

### 1.13 Customer Portal

| # | Test | Expected Result | Pass | Notes |
| --- | --- | --- | --- | --- |
| 1.13.1 | Customer login (portal) | Customer can authenticate | ☐ | |
| 1.13.2 | View own profile | Customer sees own data only | ☐ | |
| 1.13.3 | View own connection | Connection details displayed | ☐ | |
| 1.13.4 | View own invoices | Only customer's invoices listed | ☐ | |
| 1.13.5 | Create support ticket | Ticket created with customer as reporter | ☐ | |

---

## 2. Non-functional acceptance

### 2.1 Performance

| # | Criterion | Threshold | Pass | Notes |
| --- | --- | --- | --- | --- |
| 2.1.1 | Login response time | < 500ms (p95) | ☐ | |
| 2.1.2 | Customer list (50 records) | < 300ms (p95) | ☐ | |
| 2.1.3 | Dashboard load time | < 1s (p95) | ☐ | |
| 2.1.4 | Health endpoint response | < 100ms | ☐ | |
| 2.1.5 | Readiness endpoint response | < 2s (including DB check) | ☐ | |

### 2.2 Security

| # | Criterion | Expected | Pass | Notes |
| --- | --- | --- | --- | --- |
| 2.2.1 | No password reset token in response | `{requested: true}` only | ☐ | |
| 2.2.2 | SQL injection attempt blocked | Parameterized query; no data exposure | ☐ | |
| 2.2.3 | XSS attempt blocked | Security headers; no script execution | ☐ | |
| 2.2.4 | CSRF protection | SameSite cookies; no cross-origin requests | ☐ | |
| 2.2.5 | Rate limiting effective | Excessive requests return 429 | ☐ | |
| 2.2.6 | MikroTik credentials encrypted | Stored as ciphertext; decrypted only at runtime | ☐ | |
| 2.2.7 | HTTPS enforcement | HTTP redirects to HTTPS | ☐ | |
| 2.2.8 | No debug output in production | `APP_DEBUG=false`; no stack traces in 500s | ☐ | |

### 2.3 Reliability

| # | Criterion | Expected | Pass | Notes |
| --- | --- | --- | --- | --- |
| 2.3.1 | Container restart recovery | All services recover after `docker compose restart` | ☐ | |
| 2.3.2 | Database reconnection | API recovers after MariaDB restart | ☐ | |
| 2.3.3 | Redis reconnection | API recovers after Redis restart | ☐ | |
| 2.3.4 | Graceful migration failure | Failed migration rolls back; `--pretend` safe | ☐ | |

### 2.4 Documentation

| # | Criterion | Expected | Pass | Notes |
| --- | --- | --- | --- | --- |
| 2.4.1 | Developer docs complete | All 11 documents present and accurate | ☐ | |
| 2.4.2 | Operations docs complete | All 10 documents present and accurate | ☐ | |
| 2.4.3 | Deployment guide verified | Steps produce a working deployment | ☐ | |
| 2.4.4 | API reference matches routes | Route catalog reflects actual registered routes | ☐ | |

---

## 3. Sign-off

| Role | Name | Date | Signature |
| --- | --- | --- | --- |
| Release Manager | | | |
| Technical Lead | | | |
| QA Lead | | | |
| Security Reviewer | | | |
| Operations Lead | | | |

**All items must pass before production deployment is authorized.**
