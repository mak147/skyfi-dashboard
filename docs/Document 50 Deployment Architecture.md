Document 50: Deployment Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document details the architecture and strategy for deploying new versions of the SkyFi Networks applications (frontend and backend) into the Staging and Production environments. It outlines the deployment methodology, the steps involved, and the mechanisms for ensuring high availability and zero downtime during releases.

The goal is to design a deployment process that is:

Automated: Triggered by the CI/CD pipeline with minimal manual intervention.
Reliable: Predictable and repeatable across all environments.
Zero-Downtime: Allows for updates to be rolled out without interrupting service for users.
Safe: Includes mechanisms for easy and rapid rollback in case of a failed deployment.
2.0 Responsibilities
Role	Responsibility
DevOps Engineers	Implement and maintain the deployment scripts and infrastructure. Manage the release process.
Principal Architect	Design the deployment strategy (e.g., Blue-Green) and rollback procedures.
Backend/Frontend Developers	Ensure their applications are built to be "deployable"—stateless and configured via environment variables.
QA Engineers	Perform smoke tests and validation on the newly deployed environment before it receives live traffic.
3.0 High-Level Deployment Strategy
We will employ different strategies for the backend and frontend, as their technical characteristics are distinct.

Backend (PHP REST API): Blue-Green Deployment.
Justification: The backend is a state-aware application (it connects to a database). A Blue-Green strategy provides the highest level of safety by creating a complete, parallel production environment for testing before any live traffic is switched over. It makes rollbacks nearly instantaneous.
Frontend (React SPA): Continuous Deployment via CDN.
Justification: The frontend consists of static files (HTML, CSS, JS). Deploying it is a matter of updating these files in a storage location (S3) and clearing a CDN cache. This process is inherently low-risk and fast.
4.0 Backend Deployment Architecture: Blue-Green
4.1 Infrastructure Overview

For our production environment, we will maintain two identical, parallel sets of application infrastructure within our AWS Auto Scaling Groups (ASGs).

"Blue" Environment: The current, live production environment receiving all user traffic.
"Green" Environment: An idle, identical environment that is the target for the next deployment.
The Application Load Balancer (ALB) is the key component that controls which environment is live.

Blue-Green Deployment Diagram:

mermaid

graph TD
    subgraph "Before Deployment"
        ALB1[Application Load Balancer] -- "100% Traffic" --> Blue[Blue Environment (v1.2.0)];
        Green[Green Environment (Idle)]
    end

    subgraph "Deployment Phase"
        direction LR
        CI[CI/CD Pipeline] -- "1. Deploy v1.2.1" --> G1[Green Environment (v1.2.1)];
        G1 --> T1[2. Run Migrations & Tests];
        T1 -- "Tests Pass" --> C1[3. Update ALB Listener Rule];
    end

    subgraph "After Deployment"
        ALB2[Application Load Balancer] -- "100% Traffic" --> G2[Green Environment (v1.2.1)];
        B2[Blue Environment (v1.2.0 - Idle / Standby)];
    end

    style Blue fill:#cde4ff
    style G1 fill:#b4e8c8
    style G2 fill:#b4e8c8
4.2 Step-by-Step Deployment Process

Preparation: The main branch is tagged with a new version (e.g., v1.2.1) by the CI/CD pipeline. This triggers the deployment workflow.
Deploy to Green:
The deployment script targets the "Green" environment (the currently idle one).
Using AWS CodeDeploy or a similar tool, the new Docker image (skyfi-backend:v1.2.1) is pulled and deployed to the EC2 instances in the Green ASG.
Database Migrations:
Once the new application code is running on the Green environment, a script is executed to run any new database migrations.
Crucial Rule: All database migrations must be backward-compatible. The old version of the code (Blue) must be able to function correctly with the new database schema. This means no dropping columns or making breaking changes without a more complex, multi-stage deployment plan.
Testing and Validation:
The Green environment is now running the new code against the production database.
Automated smoke tests and health checks are run against the Green environment's private IP or a dedicated test listener on the ALB. This verifies that the new application can start up and connect to its dependencies correctly.
QA can perform manual validation at this stage if needed.
Traffic Switch (The "Cutover"):
If all tests pass, the core Blue-Green action occurs.
An automated script modifies the listener rule on the Application Load Balancer to instantly redirect 100% of live user traffic from the Blue Target Group to the Green Target Group.
The Green environment is now the new live "Blue" environment. The old Blue environment is now the idle "Green" environment for the next deployment.
Post-Deployment Monitoring: The system is monitored closely for any increase in errors or performance degradation.
Decommissioning (Optional): The old Blue environment can be kept running for a short period (e.g., 1 hour) to facilitate a rapid rollback. After this period, it can be scaled down to zero instances to save costs.
4.3 Rollback Procedure

A rollback is simply the reverse of the cutover.
If the new version in production shows critical errors, the release manager executes a single command.
This command modifies the ALB listener rule to switch traffic back to the old Blue environment (which is still running the previous, stable version of the code).
The rollback is nearly instantaneous and service is restored immediately. The problematic new version can then be investigated without pressure.
5.0 Frontend Deployment Architecture: CDN and S3
The frontend deployment is much simpler.

Infrastructure:

AWS S3 Bucket: A bucket configured for static website hosting.
Cloudflare: Our CDN and DNS provider, pointing to the S3 bucket.
Deployment Process:

Build: The CI/CD pipeline runs vite build to create the static dist/ directory.
Versioning: A folder named after the new version number (e.g., /v2.5.1/) is created in the S3 bucket.
Sync: The contents of the dist/ directory are synced to this new versioned folder in S3 (s3://skyfi-assets/v2.5.1/).
Pointer Update (Atomic Switch): A single index.html file at the root of the S3 bucket acts as a pointer. This file is modified to load the JavaScript and CSS assets from the new versioned folder. Overwriting this single file is an atomic operation.
Cache Invalidation: An API call is made to Cloudflare to purge its CDN cache for the application's domain. This forces browsers to fetch the new index.html and, subsequently, the new versioned assets.
Rollback Procedure:

A rollback is as simple as overwriting the root index.html file with the contents of a previous version, pointing it back to an older versioned asset folder (e.g., s3://skyfi-assets/v2.5.0/), and clearing the CDN cache again. This is extremely fast and reliable.
6.0 Environment Configuration
Immutable artifacts (Docker images, static JS files) are used across all environments.
Environment-specific configuration (database URLs, API keys, JWT secrets) is injected at runtime via environment variables.
For the backend, AWS Systems Manager Parameter Store or AWS Secrets Manager will be used to securely store these variables, which are then passed to the EC2 instances/Docker containers on startup.
For the frontend, the CI/CD pipeline will create a .env file with the correct backend API URL (staging-api.skyfinetworks.com vs. api.skyfinetworks.com) just before the vite build step.
7.0 Risks
Risk	Description	Mitigation Strategy
Breaking Database Migrations	A developer creates a migration that is not backward-compatible (e.g., renames a column that the old code relies on).	This will cause the Blue environment to fail immediately after the migration is run. This is a critical process and training issue. All developers must be trained on writing backward-compatible migrations. A multi-stage deployment (first deploying code that can handle both schemas, then running the migration, then deploying code that removes the old schema logic) is required for such changes.
Stateful Application Components	A component of the application stores state on the local filesystem or in server memory, causing issues after a deployment switches servers.	Our Software Architecture mandates a stateless application tier. All persistent state must be in the database or a distributed cache (Redis). All user-uploaded files must be stored in S3, not on the local EC2 instance.
ALB Caching/DNS Issues	DNS caching or issues with the ALB can cause some users to be directed to the old environment for a short period after the cutover.	Use low TTLs on DNS records during the deployment window. The ALB handles connection draining gracefully, ensuring existing requests to the old environment complete before it stops sending new ones.
Cost of Blue-Green	Maintaining two full production environments, even if one is mostly idle, can be expensive.	This is a trade-off for safety and reliability. The "idle" Green environment's ASG can be configured with a minimum of 1 instance and only scaled up during the deployment process, then scaled back down after the old Blue environment is decommissioned, minimizing costs.