Document 49: CI/CD Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the Continuous Integration (CI) and Continuous Deployment (CD) pipelines for the SkyFi Networks platform. It outlines the stages, tools, and workflows for automatically building, testing, and deploying the frontend and backend applications.

The goal is to design a robust, secure, and efficient CI/CD system that:

Automates the entire build-to-deploy process, reducing manual effort and errors.
Provides rapid feedback to developers on the quality and integrity of their code.
Enforces security and coding standards on every change.
Ensures that all deployments are repeatable, reliable, and auditable.
2.0 Responsibilities
Role	Responsibility
DevOps Engineers	Primary Owners. Implement, manage, and optimize the CI/CD pipelines. Maintain the runner infrastructure.
Principal Architect	Design the overall CI/CD workflow and define the stages and quality gates.
Developers	Understand the pipeline's stages and diagnose build or test failures for their specific changes.
QA Engineers	Integrate automated test suites (integration, E2E) into the pipeline and define the quality gates for promotion.
3.0 Chosen Tooling
CI/CD Platform: GitHub Actions.
Justification: As we are using GitHub for source control, GitHub Actions provides a seamlessly integrated, powerful, and highly configurable platform. It has a vast marketplace of pre-built actions, excellent support for matrix builds, and secure secret management.
Containerization: Docker.
Justification: Docker provides a consistent and portable environment for building and running our applications. Using Docker ensures that the application runs the same way on a developer's laptop as it does in the CI pipeline and in production.
Infrastructure as Code (IaC): Terraform.
Justification: Terraform will be used to define and manage our AWS infrastructure (VPCs, EC2 instances, RDS, etc.). While not directly part of the application CI/CD, the pipeline may trigger Terraform plans/applies for infrastructure changes.
4.0 High-Level CI/CD Philosophy
Pipeline as Code: All CI/CD pipelines will be defined in YAML files (.github/workflows/) stored within each application's repository. This makes the pipeline version-controlled, reviewable, and reproducible.
Separate Pipelines: The frontend and backend will have their own independent CI/CD pipelines, reflecting their separate repositories and release cycles.
Fast Feedback Loop: The CI pipeline (run on every PR) must be optimized for speed. It should complete in under 10 minutes to avoid blocking developers.
Security in the Pipeline (DevSecOps): Security checks (SAST, dependency scanning) are not optional add-ons; they are mandatory stages in the pipeline.
Immutable Artifacts: The pipeline will build a versioned, immutable artifact (a Docker image) once. This exact same artifact is then promoted through each environment (Staging, Production). We do not rebuild for different environments. Configuration is injected at runtime.
5.1 Backend CI/CD Pipeline (skyfi-backend)
This pipeline is triggered on every push to a feature branch and on every push to main.

Pipeline Stages Diagram:

mermaid

graph TD
    A(Push to Branch) --> B{CI Pipeline};
    
    subgraph B
        direction LR
        S1[1. Lint & Static Analysis] --> S2[2. Unit & Integration Tests]
        S2 --> S3[3. Security Scan]
        S3 --> S4[4. Build Docker Image]
        S4 --> S5[5. Push Image to Registry]
    end

    A -- "On Pull Request" --> B
    
    C(Merge to `main`) --> D{CD Pipeline};
    
    subgraph D
        direction TB
        R1[1. Run Release Analysis] --> R2[2. Tag & Release]
        R2 --> D1[3. Deploy to Staging]
        D1 --> D2[4. Run E2E & Smoke Tests]
    end

    E(Manual Approval<br>or Tag Promotion) --> F{Production Deployment};
    subgraph F
      direction TB
      P1[Deploy to Production] --> P2[Run Smoke Tests]
      P2 --> P3[Monitor]
    end

    D2 -- "If all pass" --> E
Stage Details:

1. Lint & Static Analysis (CI):
Runs php-cs-fixer to check for PSR-12 compliance.
Runs phpstan for static analysis to catch type errors and potential bugs.
This stage must pass for the pipeline to continue.
2. Unit & Integration Tests (CI):
Spins up a database service (e.g., MySQL in a Docker container).
Runs phpunit.
Generates a code coverage report. The build will fail if coverage drops below a defined threshold (e.g., 80%).
3. Security Scan (CI):
Runs composer audit to check for vulnerabilities in PHP dependencies.
Runs a SAST tool (e.g., SonarQube) to scan for security flaws in the source code.
High-severity findings will fail the build.
4. Build Docker Image (CI):
Uses a multi-stage Dockerfile to create an optimized, production-ready Docker image containing the PHP application.
The image is tagged with the Git commit SHA.
5. Push Image to Registry (CI):
The newly built Docker image is pushed to a container registry (e.g., Amazon ECR, GitHub Container Registry).
Release & Tag (CD - on main only):
Runs the semantic-release tool to determine the new version, generate a changelog, and create a Git tag.
Retags the Docker image with the new version number (e.g., v1.2.3).
Deploy to Staging (CD):
Triggers a deployment process (e.g., using AWS CodeDeploy or by SSH'ing to servers and running a docker-compose pull && docker-compose up -d script).
Runs database migrations against the staging database.
E2E & Smoke Tests (CD):
Runs a suite of end-to-end tests (e.g., using Cypress or Playwright) against the live Staging environment to verify critical user flows.
Runs basic smoke tests (e.g., can I hit the /api/health endpoint?).
Deploy to Production (CD):
This is a gated step. It requires manual approval from a release manager via the GitHub Actions UI or is triggered by a separate workflow that runs on new tags.
The deployment process is identical to Staging, but targets the production servers and database. A blue-green or canary deployment strategy will be used to ensure zero downtime.
5.2 Frontend CI/CD Pipeline (skyfi-frontend)
The frontend pipeline is similar but simpler, as it involves building static files rather than a server application.

Pipeline Stages Diagram:

mermaid

graph TD
    A(Push to Branch) --> B{CI Pipeline};
    
    subgraph B
        direction LR
        S1[1. Lint & Type Check] --> S2[2. Unit & Component Tests]
        S2 --> S3[3. Security Scan]
        S3 --> S4[4. Build Static Site]
    end

    C(Merge to `main`) --> D{CD Pipeline};
    
    subgraph D
        direction TB
        R1[1. Run Release Analysis] --> R2[2. Tag & Release]
        R2 --> D1[3. Deploy to Staging]
    end

    E(Manual Approval<br>or Tag Promotion) --> F{Production Deployment};
    subgraph F
      P1[Deploy to Production]
    end

    D1 --> E
Stage Details:

1. Lint & Type Check (CI):
Runs eslint and prettier --check.
Runs tsc --noEmit to perform a full TypeScript type check.
2. Unit & Component Tests (CI):
Runs vitest or jest to execute all unit and component-level tests.
Generates a code coverage report.
3. Security Scan (CI):
Runs npm audit to check for vulnerabilities in frontend dependencies.
4. Build Static Site (CI):
Runs vite build. This compiles the TypeScript/React code into a set of optimized static HTML, CSS, and JS files.
The build artifacts are stored for the deployment stage.
Release & Tag (CD - on main only):
Same as backend: runs semantic-release to version, tag, and generate a changelog.
Deploy to Staging/Production (CD):
The deployment process is very simple: it involves syncing the static build artifacts to an AWS S3 bucket.
Cloudflare (our CDN) is configured to serve files from this S3 bucket.
A cache invalidation command is sent to Cloudflare to ensure users receive the latest version.
6.0 Secrets Management
All secrets (database passwords, API keys, JWT secret) must be stored in GitHub Encrypted Secrets.
These secrets are configured at the repository or organization level and are securely passed to the pipeline runners as environment variables during a workflow run. They are never printed to logs.
7.0 Risks
Risk	Description	Mitigation Strategy
Slow Pipeline Feedback	The CI pipeline takes too long to run, causing developers to wait and reducing productivity.	Optimize heavily. Use caching for dependencies (npm, composer) and Docker layers. Run jobs in parallel (e.g., linting and testing can run at the same time). Use larger, more powerful runners for computationally intensive tasks.
Flaky Tests	Intermittent test failures in the pipeline block valid PRs and erode trust in the CI system.	Zero tolerance for flaky tests. Any test that fails intermittently must be immediately fixed or disabled. Tests should be written to be deterministic and not rely on external services or timing.
Secret Leakage	A secret is accidentally printed to a log during a CI run.	GitHub Actions automatically masks its secrets in logs. However, developers should be trained to never echo or print_r sensitive environment variables in build scripts.
Deployment Failures	A deployment script fails, leaving an environment in a broken or inconsistent state.	All deployment scripts must be idempotent and transactional where possible. A rollback plan must be in place. For database migrations, every migration must have a corresponding down method to allow for rollbacks. Blue-green deployments provide a safe way to switch traffic only after the new environment is confirmed to be healthy.