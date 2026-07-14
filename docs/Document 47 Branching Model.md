Document 47: Branching Model
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Mandated Standard

1.0 Purpose
This document provides a specific and formal definition of the branching model to be used for all development on the SkyFi Networks platform. It builds upon the Git Strategy (Document 46) to provide a clear, prescriptive guide for creating and managing branches.

The goal of this document is to serve as a simple, unambiguous reference for developers, ensuring that all branching activities are consistent and align with our chosen workflow.

2.0 Responsibilities
Role	Responsibility
Development Lead	Ensure all branches created by the team adhere to the naming conventions and lifecycle defined here.
All Developers	Follow this model for all development work.
DevOps Engineers	Configure branch protection rules and CI/CD triggers based on this model.
3.0 Chosen Model: GitHub Flow
We will officially adopt the GitHub Flow as our branching model. This model is characterized by its simplicity and direct relationship with continuous deployment practices.

Core Tenets:

The main branch is the single source of truth.
The main branch must always be stable and deployable.
All development is done on short-lived feature branches.
Changes are integrated into main via Pull Requests (PRs).
Diagrammatic Representation:

mermaid

graph LR
    subgraph Repository
        direction LR
        A(main branch)
        B(Feature Branch)
        C(Pull Request)

        B -- Creates --> C
        C -- Merges into --> A
        A -- Is source for --> B
        A -- Deploys to --> Production
    end
4.0 Branch Definitions
There are only two categories of branches in this model: a single long-lived branch and infinite short-lived branches.

4.1 The Main Branch

Name: main
Purpose: The primary branch of the repository. It represents the official, stable, and production-ready version of the codebase.
Lifecycle: Perpetual. It is never deleted.
Rules:
Direct pushes are forbidden.
All changes must be merged via an approved Pull Request.
All CI status checks must pass before a PR can be merged.
The history must be linear and clean, enforced by a "Squash and Merge" strategy.
Deployment: A successful merge to main should automatically trigger a deployment to the Staging environment. A manual promotion or tagging process then triggers deployment to Production.
4.2 Feature Branches

Name: Must follow the convention: {type}/{issue-id}-{short-description}
Examples: feature/SFW-123-customer-dashboard, fix/SFW-125-login-csrf-bug
Purpose: To isolate the development of a new feature, bug fix, or any other change. This prevents unstable code from affecting other developers or the main branch.
Source: Always created from the latest version of main.
Command: git checkout main && git pull && git checkout -b feature/SFW-XYZ-new-thing
Lifecycle: Short-lived. A feature branch should exist only as long as it takes to develop and review the feature. Ideally, this is a few hours to a few days. Long-lived feature branches (weeks or months) are an anti-pattern and should be avoided by breaking down large features.
Deletion: Once the corresponding Pull Request has been merged into main, the feature branch must be deleted. This keeps the repository clean and prevents confusion. Most Git providers have an option to automatically delete the branch upon merging a PR.
5.0 Special Cases (To be used sparingly)
While GitHub Flow is simple, a few special cases may arise.

5.1 Hotfix Branches

Scenario: A critical, production-breaking bug is discovered that needs to be fixed immediately.
Process: The process is exactly the same as a normal feature branch, just with higher urgency.
Create a branch from main: fix/SFW-911-production-login-outage.
Make the minimal necessary change to fix the bug.
Push the branch and open a PR.
The review and CI process is expedited.
Once merged to main, the fix is deployed immediately.
Justification: Following the same process ensures that even urgent fixes are reviewed and tested, preventing a rushed fix from introducing new bugs.
5.2 Release Branches (Optional / Future)

Scenario: If the project moves from a continuous deployment model to a scheduled release cadence (e.g., "quarterly releases"), we may introduce release branches.
Name: release/v1.1.0
Process (if adopted):
When a release is planned, a release branch is created from main.
This branch enters a "feature freeze." Only critical bug fixes are merged into it.
The QA team performs final regression testing on this stable branch.
Once certified, the release branch is tagged and deployed to production.
Any fixes made on the release branch must also be merged back into main.
Current Status: For v1.0 and our initial agile approach, release branches are not part of our model. We will deploy directly from main. This can be re-evaluated as the product and team mature.
6.0 Workflow Summary
A Developer's Daily Workflow:

Start a new task:
git checkout main
git pull origin main (Sync with the latest stable code)
git checkout -b feature/SFW-456-new-feature (Create your new branch)
Develop:
Write code.
Make small, atomic commits with conventional commit messages.
git push origin feature/SFW-456-new-feature (Push frequently to back up your work).
Prepare for Review:
git pull origin main --rebase (Update your branch with the latest changes from main and place your commits on top, keeping history linear).
(Optional but recommended) git rebase -i origin/main to clean up and squash your commits.
Open Pull Request:
Go to the Git provider UI (GitHub/GitLab).
Open a PR from feature/SFW-456-new-feature to main.
Fill out the PR template.
Assign reviewers.
Review & Merge:
Address comments from reviewers.
Wait for CI checks to pass.
Once approved, a lead developer will Squash and Merge the PR.
Cleanup:
The feature branch is automatically deleted.
git checkout main && git pull to get the newly merged changes.
7.0 Risks
Risk	Description	Mitigation Strategy
Unstable main Branch	A buggy PR is merged, breaking the main branch and blocking all deployments and new feature branches.	This is the primary risk of this model. Mitigation is multi-layered: 1) Thorough code reviews. 2) Comprehensive automated testing in the CI pipeline. 3) A robust QA process on the Staging environment before promoting a build to Production.
Overly Complex Branches	Developers create branches off of other feature branches, leading to a confusing and hard-to-merge dependency graph.	This is strictly discouraged. All feature branches must be created from main. If one feature depends on another, the first feature's PR should be merged into main before the second feature branch is rebased on top of the new main.