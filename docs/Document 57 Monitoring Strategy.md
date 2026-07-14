Document 57: Monitoring Strategy
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Guiding Standard

1.0 Purpose
This document specifies the monitoring, observability, and alerting strategy for the SkyFi Networks platform. It defines what we will monitor, the tools we will use, and how we will be alerted to problems before they significantly impact our users.

The goal is to move from a reactive "wait for things to break" model to a proactive "observe, predict, and prevent" model by establishing deep visibility into all layers of the application and infrastructure.

2.0 Responsibilities
Role	Responsibility
DevOps Engineers / SRE Team	Primary Owners. Implement, configure, and maintain the monitoring and alerting stack. Act as first responders to alerts.
Principal Architect	Design the monitoring strategy to ensure it provides visibility into all key architectural components.
Developers (Frontend & Backend)	Instrument the application code with custom metrics and traces. Use monitoring data to debug performance issues.
Network Engineers	Consume network-specific dashboards and define alerting thresholds for network device health.
3.0 Monitoring Philosophy: The Three Pillars of Observability
Our strategy is built on the three pillars of modern observability, which work together to provide a complete picture of system health.

Logs: A detailed, event-by-event record of what happened. (Covered in detail in Doc 24).
Metrics: Aggregated, numerical data about the system's performance over time. Metrics are for identifying trends and unknown unknowns.
Traces: A detailed view of a single request's journey through all the components of the distributed system. Traces are for debugging specific performance problems.
Observability Relationship:

A metric (e.g., a spike in API error rate) tells you that you have a problem.
A trace helps you pinpoint where in the system the problem is occurring (e.g., in a slow database query).
A log tells you the specific, ground-level details of why the problem happened (e.g., the exact error message from the database).
4.0 Monitoring Architecture and Tooling
We will use a consolidated, powerful platform to ingest and correlate data from all three pillars.

Primary Tool: Datadog (or a comparable platform like New Relic, Dynatrace).
Justification: Datadog provides a unified platform for infrastructure monitoring, Application Performance Monitoring (APM), log management, and real-user monitoring. Using a single platform allows us to seamlessly correlate data (e.g., jump from a slow transaction trace directly to the logs for that specific request).
Secondary Tool (for specific use cases):
AWS CloudWatch: Used for fundamental AWS infrastructure metrics (CPU, RAM) and for triggering infrastructure-level actions like Auto Scaling.
Pingdom/UptimeRobot: External uptime monitoring to check our public-facing endpoints from multiple locations around the world.
Data Flow Diagram:

mermaid

graph TD
    subgraph "Data Sources"
        A[AWS Infrastructure (EC2, RDS, ALB)]
        B[PHP Backend Application]
        C[React Frontend Application]
        D[MikroTik Routers (via SNMP)]
        E[External Uptime Probes]
    end

    subgraph "Datadog Platform"
        F[Metrics]
        G[Traces (APM)]
        H[Logs]
        I[Real User Monitoring (RUM)]
    end

    subgraph "Outputs"
        J[Dashboards]
        K[Alerts]
    end

    A -- "CloudWatch Integration" --> F
    B -- "Datadog Agent/APM" --> F
    B -- "APM Tracer" --> G
    B -- "Log Forwarder" --> H
    C -- "RUM SDK" --> I & G & H
    D -- "SNMP Agent" --> F
    E -- "Datadog Synthetic Monitoring" --> F
    
    I --> J
    F --> J
    G --> J

    F -- "Thresholds" --> K
    H -- "Patterns" --> K
    
    K --> L[PagerDuty / Slack / Email]
5.0 What We Will Monitor: Key Metrics by Layer
5.1 Infrastructure Monitoring (The GOLD Signals)
These are fundamental metrics for our AWS resources, collected by the Datadog agent and CloudWatch.

Load Balancer (ALB):
RequestCount, TargetConnectionErrorCount, HTTPCode_Target_5XX_Count
TargetResponseTime (p50, p90, p99)
Application Servers (EC2):
CPUUtilization, MemoryUtilization, DiskSpaceUtilization
NetworkIn/NetworkOut
Database (RDS):
CPUUtilization, DatabaseConnections, FreeableMemory, FreeStorageSpace
ReadIOPS/WriteIOPS, ReadLatency/WriteLatency
ReplicaLag (for the DR read replica)
Cache (Redis):
CPUUtilization, MemoryUsage, CacheHitRate, Evictions
5.2 Application Performance Monitoring (APM)
This provides deep insight into our backend code's performance, collected by the Datadog APM tracer library installed in our PHP application.

The RED Method:
Rate: Requests per second for each API endpoint.
Errors: Error rate for each endpoint (percentage of 5xx responses).
Duration: Latency distribution (p50, p90, p99) for each endpoint.
Trace Details: For slow or erroneous requests, we will have a detailed flame graph showing the time spent in each function call, database query, and external API call.
Custom Metrics: We will instrument our code to send custom business metrics, e.g., skyfi.invoices.generated.count, skyfi.payments.processed.value.
5.3 Real User Monitoring (RUM)
This provides insight into the frontend performance as experienced by real users, collected by the Datadog RUM JavaScript SDK.

Core Web Vitals: LCP, FID, CLS.
Page Load Times: For different pages and geographic locations.
Frontend Errors: Captures all JavaScript errors that occur in the user's browser.
User Journeys: Tracks which views users visit, helping us understand user behavior.
5.4 Network Hardware Monitoring
Protocol: SNMP (Simple Network Management Protocol).
Implementation: A Datadog agent with SNMP capabilities will be run on a server within our network. It will be configured to poll our MikroTik routers.
Key Metrics (OIDs):
ifInOctets/ifOutOctets: Bandwidth usage per interface.
Uptime: Device uptime.
CPU Load
Temperature
Ping response time from the monitoring server to the router.
6.0 Dashboards
We will create several role-specific dashboards in Datadog.

Global Health Dashboard (for SRE/DevOps): A high-level overview combining the most critical metrics from every layer: API error rate, DB CPU, queue depth, etc. This is the "first look" screen during an incident.
Backend Application Dashboard: A detailed view of API performance, focusing on the RED method metrics for every endpoint, plus JVM/PHP runtime stats.
Database Dashboard: A deep dive into RDS performance, showing latency, IOPS, active connections, and slow query logs.
Network Operations Dashboard: A map-based view showing the up/down status of all managed MikroTik routers, along with key performance metrics like latency and bandwidth usage for major backhauls.
7.0 Alerting Strategy
Alerts are the proactive component of monitoring. Our alerting philosophy is "Alert on symptoms, not causes."

Alert on User-Facing Problems: We alert on high error rates or high latency (the symptoms), not on high CPU (a potential cause). This reduces alert noise.
Tiered Severity:
P1 - Critical (Wake-up call): An alert that requires immediate human intervention, sent to PagerDuty.
Example: API Error Rate > 5% for 5 minutes, Production website is down from external probes.
P2 - Warning (Ticket/Email): An issue that needs to be investigated but is not immediately service-impacting.
Example: Staging deployment failed, Database ReplicaLag > 10 minutes, Disk space > 80%.
Self-Healing: Where possible, alerts should trigger automated actions. For example, an alert for a failed application server instance should trigger the ASG to terminate it and launch a new one.
Example Alerts:

Metric	Threshold	Severity	Notification Channel
ALB HTTPCode_Target_5XX_Count	> 10 in 1 minute	P1 - Critical	PagerDuty + Slack
RDS CPUUtilization	> 90% for 15 minutes	P1 - Critical	PagerDuty + Slack
SQS ApproximateAgeOfOldestMessage	> 1 hour	P2 - Warning	Slack + Email
JS Error Rate (RUM)	> 5% of all sessions	P2 - Warning	Slack
Certificate Expiry	< 30 days remaining	P2 - Warning	Email
8.0 Risks
Risk	Description	Mitigation Strategy
Alert Fatigue	Too many low-priority or "flappy" alerts cause the team to ignore them, leading to a real incident being missed.	This is the biggest risk. Alert thresholds must be carefully tuned. Use multi-window strategies (e.g., "alert if X happens for Y minutes"). Prioritize alerting on user-facing symptoms. Regularly review and decommission noisy, non-actionable alerts.
Monitoring "Blind Spots"	A critical component of the system is not being monitored, and it fails silently.	Create a monitoring checklist as part of the "definition of done" for any new service or feature. The architecture should be reviewed to ensure all components (queues, caches, databases, external services) have basic health and performance monitoring in place.
Cost of Monitoring	Advanced monitoring platforms can be expensive, with costs scaling with data ingestion volume.	Be selective about what is logged at the INFO level. Use sampling for traces on high-traffic endpoints. Leverage Datadog's tools for managing and forecasting ingestion to control costs.
Tool Sprawl	Using too many different, disconnected monitoring tools makes it difficult to correlate data during an incident.	This is why we have chosen a unified platform like Datadog as our primary tool. It brings metrics, traces, and logs together in one place, which is invaluable during a high-stress investigation.