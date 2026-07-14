Document 52: Disaster Recovery
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document outlines the Disaster Recovery (DR) plan for the SkyFi Networks platform. It specifies the strategy, procedures, and responsibilities for recovering from a large-scale or catastrophic failure that renders our primary production environment inoperable.

The goal is to design and document a DR plan that meets our business continuity objectives, specifically the Recovery Time Objective (RTO) and Recovery Point Objective (RPO) defined in our non-functional requirements. This plan ensures that we can restore service in a timely manner even in the face of a major disaster.

2.0 Responsibilities
Role	Responsibility
DevOps Lead / DR Commander	Leads the DR event, executing the runbook and coordinating the recovery team.
Principal Architect	Designs the DR strategy and maintains this plan. Participates in DR drills.
DevOps Engineers	Implement and maintain the DR infrastructure and automation scripts. Execute technical recovery steps during a drill or actual event.
Executive Leadership	Formally declare a disaster, which triggers the execution of this plan.
Communications Lead	Manage all internal and external communications during a DR event.
3.0 Scope and Disaster Scenarios
This plan is designed to address large-scale failures. It is not for minor issues like a single server failure (which is handled by Auto Scaling) or accidental data deletion (handled by Point-in-Time Recovery).

In-Scope Scenarios:

Full AWS Region Failure: A complete and prolonged outage of our primary AWS region (e.g., us-east-1).
Catastrophic Data Corruption: A widespread, unrecoverable corruption of our primary database that cannot be fixed with Point-in-Time Recovery.
Major Security Breach: A security incident that requires taking the entire primary environment offline for forensic investigation.
4.0 DR Strategy: Pilot Light on AWS
We will adopt a Pilot Light strategy for Disaster Recovery.

Description: A minimal version of the core infrastructure is kept running in a separate, designated DR region. The core data is continuously replicated to this DR region. In a disaster, this "pilot light" is rapidly expanded to a full-scale production environment.
Primary Region: e.g., us-east-1 (N. Virginia)
DR Region: e.g., us-west-2 (Oregon)
Justification:

Cost-Effective: This is significantly cheaper than a full-scale, hot-standby environment. We only pay for the minimal idle resources and the full-scale infrastructure during a drill or actual disaster.
Faster RTO than Cold Standby: Since the core data and networking are already in place, the recovery time is much faster than starting from scratch (a "cold" site).
Meets Business Objectives: This strategy is designed to meet our target RTO of < 4 hours.
DR Architecture Diagram:

mermaid

graph TD
    subgraph "Primary Region (us-east-1) - Normal Operations"
        P_ALB[Production ALB] --> P_ASG[Production ASG (Running)]
        P_ASG --> P_RDS[Production RDS (Master)]
        P_S3[Production S3 Bucket]
    end

    subgraph "DR Region (us-west-2) - Pilot Light (Idle)"
        DR_VPC[VPC & Networking]
        DR_ASG[DR ASG (Min Instances: 0)]
        DR_RDS_R[DR RDS (Read Replica)]
        DR_S3[DR S3 (Replicated Bucket)]
    end

    subgraph "Replication (Continuous)"
        P_RDS -- "Cross-Region Replication" --> DR_RDS_R
        P_S3 -- "Cross-Region Replication" --> DR_S3
    end

    style DR_ASG fill:#f9f9f9,stroke:#ccc,stroke-dasharray: 5 5
    style DR_RDS_R fill:#cde4ff
    style DR_S3 fill:#cde4ff
5.0 Pilot Light Infrastructure Components (in DR Region)
The following resources are pre-provisioned and maintained in the DR region (us-west-2):

Networking: A complete VPC, subnets, security groups, and routing tables that mirror the production environment. This is managed via Terraform.
Database: An RDS Read Replica of our primary production database, configured with cross-region replication. This replica continuously receives updates from the master.
S3 Bucket: A backup S3 bucket that is the target for Cross-Region Replication (CRR) from our primary bucket.
Auto Scaling Group (ASG): An ASG for our backend application is defined, but its desired/min/max instance count is set to zero.
DNS: A Route 53 Failover Routing Policy is configured for our primary domain names (api.skyfinetworks.com, app.skyfinetworks.com). It has a primary record pointing to the primary region's ALB and a secondary record pointing to the DR region's (not yet created) ALB. Health checks monitor the primary endpoint.
6.0 Disaster Recovery Execution Plan (The Runbook)
This is the step-by-step procedure to be executed upon the declaration of a disaster.

Phase 1: Failover (Time: 0 to 60 minutes)

Declare Disaster: Executive leadership gives the official "go" order. The DR Commander initiates the plan.
Promote DR Database:
The RDS Read Replica in the DR region is "promoted" to become a standalone master database. This action breaks the replication link and makes the database writable.
This step defines our RPO. The replication lag is typically seconds to a few minutes, meaning minimal data loss.
Scale Up Application Tier:
The ASG in the DR region is updated to have a desired/min instance count matching the production configuration (e.g., min: 2, desired: 2).
This triggers the ASG to launch new EC2 instances running the latest stable application Docker image.
Launch Load Balancer:
An Application Load Balancer is provisioned in the DR region (this can be scripted with Terraform or the CLI).
The new EC2 instances are registered as targets.
Update Configuration:
The application configuration is updated to point to the newly promoted DR database endpoint. This is done by updating the relevant entries in AWS Secrets Manager for the DR environment.
Phase 2: DNS Failover (Time: 60 to 90 minutes)

Verify Health: Automated smoke tests are run against the new ALB's direct IP/DNS name to confirm the application is running, connected to the database, and serving requests correctly.
Initiate DNS Failover:
The Route 53 health check on the primary region's endpoint will have failed.
Route 53's failover routing policy is manually or automatically triggered to switch all DNS queries for our domains to the CNAME of the new ALB in the DR region.
Phase 3: Service Restoration & Communication (Time: 90 to 180 minutes)

Cache Warming: Scripts may be run to warm up application caches.
Validation: The recovery team performs manual checks on critical application functions.
Service Resumed: As DNS propagation completes (typically minutes to an hour), users will be directed to the new, active environment in the DR region.
Public Communication: The Communications Lead updates the public status page and notifies customers that service has been restored.
Total Estimated RTO: < 3 hours.

7.0 Failback Procedure
Failback is the process of returning to the primary region once the disaster has been resolved. This is a planned and non-urgent activity.

Resynchronize Data:
A new RDS instance is launched in the primary region.
Using a tool like AWS DMS (Database Migration Service), data is replicated from the (now master) DR database back to the new instance in the primary region.
Provision Primary Environment: The primary infrastructure is reprovisioned and verified.
Schedule Failback Window: A maintenance window is announced to users.
Execute Failback:
The application is put into a brief "maintenance mode" (read-only).
Final data sync is completed.
The roles are reversed: the primary region's DB becomes master again.
The Route 53 DNS records are switched back to point to the primary region's ALB.
The application is taken out of maintenance mode.
Decommission DR: The DR environment is scaled back down to its "pilot light" state.
8.0 DR Drills (Testing)
Frequency: A full DR failover drill will be conducted annually. A partial, "tabletop" exercise will be conducted quarterly.
Process: The annual drill will follow the entire DR Execution Plan, failing over to the DR region and running the application there for a few hours. This is the only way to validate the plan, test the automation, and accurately measure the RTO.
Goal: The goal of a drill is not to be perfect, but to find weaknesses in the plan, scripts, and documentation. All findings are documented and used to improve the DR plan.
9.0 Risks
Risk	Description	Mitigation Strategy
"DR Drift"	The configuration of the DR environment slowly drifts out of sync with production over time.	Infrastructure as Code (Terraform) is the primary mitigation. Both the production and DR environments should be managed by the same set of version-controlled Terraform modules, ensuring their underlying structure is identical. Regular DR drills will also uncover any drift.
Data Replication Lag	At the moment of disaster, the RDS read replica is significantly behind the master, leading to more data loss than the RPO allows.	The RDS replication lag is a critical metric that must be monitored continuously via CloudWatch. An alert must be configured to trigger if the lag exceeds a certain threshold (e.g., 5 minutes).
Human Error during a Crisis	In a high-stress disaster scenario, a team member makes a mistake executing the runbook.	Automate everything possible. The failover process should be a single script or a few well-defined commands. The runbook must be a simple checklist, not a complex manual. Regular drills build muscle memory and reduce panic.
Failback is Harder than Failover	The process of resynchronizing data back to the primary region is complex and error-prone.	The failback plan must be tested as part of the DR drill. The use of managed services like AWS DMS is highly recommended to simplify this process.
