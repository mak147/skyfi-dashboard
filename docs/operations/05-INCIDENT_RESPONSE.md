# Operations Guide 05 — Incident Response Playbook

**Phase:** 5 — Operations Documentation  
**Audience:** Incident Commanders (IC), SRE Engineers, Network Engineers, Customer Support Leads, Helpdesk Operators  
**Status:** Production-Ready Standard  
**Last reviewed:** 2026-07-15

---

## 1.0 Overview & Lifecycle

An **Incident** is defined as any unplanned disruption or degradation of service that affects the performance, usability, or security of the SkyFi ISP Management System. 

The incident response lifecycle consists of six distinct phases:

```
[ 1. Identification ] -> [ 2. Triage & Classify ] -> [ 3. Containment ]
                                                            |
[ 6. Post-Mortem (RCA) ] <- [ 5. Recovery & Close ] <- [ 4. Eradication ]
```

1.  **Identification:** Detecting the anomaly via automated alerting, support desk reports, or external monitoring probes.
2.  **Triage & Classify:** Assessing the scope and determining the Severity Level.
3.  **Containment:** Halting the immediate impact of the incident (e.g., blocking malicious IPs, applying rate limiting, restarting a failed server).
4.  **Eradication:** Identifying and resolving the underlying root cause (e.g., rolling back a bad release, repairing a corrupted index).
5.  **Recovery & Close:** Confirming normal service is restored and ending the incident bridge.
6.  **Lessons Learned:** Conducting a post-mortem to prevent future occurrences.

---

## 2.0 Incident Severity Levels

To allocate resources effectively, incidents are categorized into one of four severity levels:

| Severity | Description | Immediate Impact Example | SLA Response | SLA Resolution |
| --- | --- | --- | --- | --- |
| **SEV-1** (Critical) | System-wide outage or total loss of a core business unit. High revenue or regulatory risk. | *   Payment gateway completely down (customers cannot pay).<br>*   PPPoE database lock (cannot authorize router logins).<br>*   Data breach or active unauthorized root access. | 15 Mins | < 4 Hours |
| **SEV-2** (High) | Major business operations degraded. Workarounds exist but are complex or manual. | *   Customer Portal is inaccessible, but administration tools are up.<br>*   SNMP background workers failing (NOC cannot view current router speeds).<br>*   Unable to provision new customer connections. | 30 Mins | < 12 Hours |
| **SEV-3** (Medium) | Non-critical feature or module degraded. Platform is largely functional. | *   Support ticket email notifications are lagging by 1 hour.<br>*   Inventory and purchasing search filters returning 500 errors.<br>*   Minor reporting exports timeout under high load. | 2 Hours | < 3 Days |
| **SEV-4** (Low) | Aesthetic bugs, cosmetic errors, or general technical queries. | *   Invoice layout has minor spacing issues in specific browsers.<br>*   NOC Dashboard graphs have slightly misaligned legends.<br>*   Documentation requests. | 12 Hours | Next Release |

---

## 3.0 Core Incident Roles

During a **SEV-1** or **SEV-2** incident, the following roles must be assigned immediately to prevent overlapping responsibilities:

*   **Incident Commander (IC):** 
    *   Holds ultimate authority over technical execution.
    *   Directs investigations, authorizes restarts/rollbacks, and runs the active communication bridge.
    *   *SRE Lead or Senior DevOps Engineer.*
*   **Communications Lead (CL):**
    *   Acts as the shield for the technical team.
    *   Monitors helpdesk volumes, drafts customer notifications, and updates the public status page.
    *   *Support Lead or Product Manager.*
*   **Technical Lead (TL):**
    *   Leads hands-on troubleshooting, reads logs, runs SQL queries, and analyzes system state.
    *   *DevOps Engineer, DBA, or Principal Backend Engineer.*

---

## 4.0 Step-by-Step Triage & Containment Protocol

When an alert triggers or an outage is reported:

### Step 1: Initialize the Bridge (For SEV-1 & SEV-2)
1.  Open the `#incident-war-room` channel in Slack.
2.  Start an ad-hoc Zoom or Teams call and paste the link into the channel header.
3.  Assign the roles of IC, CL, and TL.

### Step 2: Establish Containment (Stop the Bleeding)
Do not wait to solve the root bug if you can contain the impact first.
*   **If High API Latency/Memory Leak:** Restart PHP-FPM worker pools:
    ```bash
    docker compose -f docker-compose.prod.yml exec backend kill -USR2 1
    ```
*   **If High DB CPU Lockup:** Identify and terminate the blocking query:
    ```sql
    -- 1. Find the connection ID of the offending query
    SHOW PROCESSLIST;
    -- 2. Kill the thread
    KILL [ConnectionID];
    ```
*   **If DDoS or Bruteforce Attack:** Ban the offending IP addresses using firewall rules on the edge load balancer or host:
    ```bash
    iptables -A INPUT -s [OFFENDING_IP] -j DROP
    ```

### Step 3: Gather Diagnostics & Logs
The Technical Lead runs immediate diagnostic sweeps:
```bash
# Check container status
docker compose -f docker-compose.prod.yml ps

# Check Redis memory footprint
docker compose -f docker-compose.prod.yml exec redis redis-cli info memory

# Check active disk utilization
df -h
```

---

## 5.0 Incident Communications Templates

The Communications Lead must provide standard, calm, and objective notifications to customers and staff.

### 5.1 SEV-1 Internal Email (Notification to Company Executives)
**Subject:** `URGENT: SEV-1 Incident Declared - SkyFi Platform Outage`

> **Incident Commander:** [Name]  
> **Severity:** SEV-1 (Critical)  
> **Impacted Systems:** SkyFi Customer Portal & Active PPPoE Auth  
> **Estimated Start Time:** 2026-07-15 17:00 UTC  
>
> **Summary of Outage:**  
> The SRE team has identified a major degradation in database query latency, preventing customer portals from authenticating and blocking new router connection requests.
>
> **Actions Underway:**  
> Technical Lead is currently optimizing index layouts and analyzing locked processes on MariaDB. SRE is preparing a temporary container restart to clear zombie threads.
>
> **Next Update:** 2026-07-15 17:30 UTC

### 5.2 External Status Page Update (For Public Customers)
**Subject:** `Service Interruption: Billing & Portal Access Degradation`

> **Update (Investigating):** We are currently experiencing an unexpected interruption affecting access to the Customer Portal and online payment systems. Our engineering team is actively investigating the issue, and we are working to restore full functionality as quickly as possible.
>
> **Impact:** Active broadband connections remain operational. Only account management, portal authentication, and manual package purchases are temporarily affected.

---

## 6.0 Post-Mortem & Root-Cause Analysis (RCA) Template

Within 48 hours of a SEV-1 or SEV-2 incident, a blameless post-mortem meeting must be held. The Incident Commander is responsible for writing the formal RCA document using this standard template:

```markdown
# SkyFi Incident Post-Mortem: [Incident Title]
**Incident Date:** YYYY-MM-DD  
**Incident ID:** INC-XXXXX  
**Severity:** SEV-[X]  
**Lead Investigator / IC:** [Name]  

## 1.0 Executive Summary
A brief 3-sentence summary of what happened, who was impacted, and how it was resolved.

## 2.0 Incident Timeline (UTC)
*   **17:00** - Incident starts.
*   **17:05** - Datadog alert triggers for API 5xx errors.
*   **17:10** - Helpdesk reports portal login failures.
*   **17:15** - SEV-1 declared; war-room opened.
*   **17:25** - Bad migration identified as locking customer index.
*   **17:40** - Migration rolled back, database restarted.
*   **17:50** - Verification checks complete; incident closed.

## 3.0 Root Cause Analysis (The 5 Whys)
1. Why was the system slow? -> Database index locks.
2. Why were indices locked? -> A schema migration was executing.
3. Why did it lock production? -> The migration ran without a pre-check lock timeout.
4. Why did it skip precheck? -> Review procedures were bypassed during emergency release.
5. Why were procedures bypassed? -> Lack of enforced automated validation in the deployment pipeline.

## 4.0 Preventive Action Items
| Action Item | Owner | Target Date | Status |
| --- | --- | --- | --- |
| Implement lock timeouts in Migrator | [Name] | YYYY-MM-DD | Open |
| Configure CI to block bypass | [Name] | YYYY-MM-DD | Open |
```
