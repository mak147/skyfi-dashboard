Document 48: Versioning Strategy
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Mandated Standard

1.0 Purpose
This document specifies the versioning strategy for the SkyFi Networks software, including the backend API and the frontend application. It defines the versioning scheme, how version numbers are determined and applied, and how they relate to our development and release process.

The goal is to establish a clear, meaningful, and consistent versioning system that:

Communicates the nature and impact of changes to all stakeholders.
Enables reliable dependency management between the frontend and backend.
Provides a clear reference point for support, documentation, and issue tracking.
Automates the release and changelog generation process.
2.0 Responsibilities
Role	Responsibility
Development Lead / Release Manager	Oversee the versioning and tagging process. Ensure versions are incremented correctly.
All Developers	Adhere to the Conventional Commits specification, as this is the foundation for automated versioning.
DevOps Engineers	Implement the automation scripts for version bumping, changelog generation, and tagging within the CI/CD pipeline.
Product & Support Teams	Use version numbers to track features, bug fixes, and customer-reported issues.
3.0 Chosen Scheme: Semantic Versioning (SemVer) 2.0.0
We will strictly adhere to the Semantic Versioning (SemVer) specification. This is the industry standard for software versioning and provides a clear, universally understood meaning for each part of a version number.

Format: MAJOR.MINOR.PATCH

MAJOR version: Incremented for incompatible API changes or major architectural shifts. This signifies a breaking change.
MINOR version: Incremented for new functionality added in a backward-compatible manner.
PATCH version: Incremented for backward-compatible bug fixes.
Additional Labels:

Pre-release: An optional label for unstable releases (e.g., 1.0.0-alpha.1, 1.0.0-beta.2).
Example Lifecycle:

0.1.0: Initial development release.
0.1.1: A bug fix is deployed.
0.2.0: A new feature (e.g., Hotspot Management) is added.
1.0.0: The first major, stable production release.
1.0.1: A post-launch bug fix.
1.1.0: A new, backward-compatible feature (e.g., a new report) is added.
2.0.0: A significant, backward-incompatible change is made to the API.
4.0 Versioning Strategy by Application
The frontend and backend are separate applications and will be versioned independently.

Backend (skyfi-api): The SemVer number for the backend directly corresponds to the version of the REST API.
The MAJOR version of the backend's SemVer number is the API version used in the URL (e.g., backend version v2.1.3 corresponds to API path /api/v2/...).
This provides a clear and direct link between the deployed code and the API contract.
Frontend (skyfi-frontend): The frontend will also follow SemVer. Its version indicates the state of the client-side application.
A MINOR version bump typically corresponds to a new feature view being added.
A MAJOR version bump might occur if the frontend undergoes a significant redesign or makes a breaking change in its dependency on the backend API version (e.g., it moves from consuming /api/v1 to /api/v2).
5.0 Automation Strategy
Manually managing version numbers is error-prone. We will automate the process using our Git history.

Foundation: Strict adherence to the Conventional Commits standard (defined in Doc 46) is mandatory. The <type> of each commit (feat, fix, etc.) provides the semantic information needed for automation.
Tooling: We will use a standard, automated versioning and changelog tool, such as semantic-release or a similar alternative.
Process:
A developer merges a Pull Request into the main branch. The PR was squashed into a single commit with a conventional commit message (e.g., feat(billing): add support for credit notes).
This merge to main triggers a "release analysis" job in the CI/CD pipeline.
The semantic-release tool analyzes all commits made to main since the last release tag.
Based on the commit messages, it determines the next version number:
If it finds at least one feat commit, it will increment the MINOR version.
If it only finds fix commits, it will increment the PATCH version.
If it finds a commit message with BREAKING CHANGE: in the footer, it will increment the MAJOR version.
The tool then automatically performs the following actions:
a. Bumps the version number in the package.json (frontend) or a similar version file (backend).
b. Generates a CHANGELOG.md file based on the commit messages.
c. Commits the package.json and CHANGELOG.md files.
d. Creates a new Git tag with the version number (e.g., v1.2.0).
e. Pushes the commit and the tag to the remote repository.
Automation Workflow Diagram:

mermaid

graph TD
    A[PR merged to `main`] --> B{CI/CD Pipeline Triggered};
    B --> C[Run 'semantic-release' tool];
    C --> D{Analyze commits since last tag};
    D -- "Found `feat` commit" --> E[Determine next version: MINOR bump];
    D -- "Only `fix` commits" --> F[Determine next version: PATCH bump];
    D -- "Found `BREAKING CHANGE`" --> G[Determine next version: MAJOR bump];
    E --> H; F --> H; G --> H;
    H{Perform Release Actions};
    H --> I[1. Update version file];
    I --> J[2. Generate CHANGELOG.md];
    J --> K[3. Commit version & changelog files];
    K --> L[4. Create Git Tag (e.g., v1.2.0)];
    L --> M[5. Push to origin];
    M --> N{Trigger Deployment Pipeline};
6.0 Git Tagging
Format: The Git tag must be prefixed with v. Example: v1.2.3.
Purpose: Tags create an immutable pointer to a specific commit in the repository's history, representing an official release.
Automation: Tags will be created and pushed exclusively by the automated semantic-release process. Manual tagging is forbidden for official releases to ensure consistency.
7.0 Displaying the Version in the Application
The application version must be easily accessible for support and debugging purposes.

Frontend: The version number from package.json will be injected into the application as an environment variable at build time. It will be displayed in the footer of the application or in the "About" section of the user profile menu.
Backend: A dedicated, unauthenticated API endpoint /api/health or /api/status will be created. It will return a JSON object containing the application status and the current version number.
JSON

{
  "status": "ok",
  "version": "1.2.3",
  "timestamp": "2023-10-27T12:00:00Z"
}
8.0 Pre-release Strategy (for major versions)
When working on a major, backward-incompatible version (e.g., v2.0.0), we will use a dedicated long-lived branch.

A new branch, next, is created from main.
Developers working on the new version create their feature branches from next and merge PRs back into next.
The semantic-release tool is configured to run on the next branch as well, but it will generate pre-release tags (e.g., v2.0.0-alpha.1, v2.0.0-alpha.2, etc.).
This allows us to deploy the alpha/beta versions to a separate testing environment.
Once v2.0.0 is ready for release, the next branch is merged into main, and the final v2.0.0 tag is created on main.
9.0 Risks
Risk	Description	Mitigation Strategy
Incorrect Version Bumping	A developer uses the wrong Conventional Commit type (e.g., uses fix for a new feature), resulting in an incorrect version increment.	Developer education is key. The team must understand the importance of the commit message format. Code reviewers should also check the squashed commit message on a PR before merging to ensure it's correct.
Complex Release History	The automated process creates too many small patch releases, making the release history noisy.	This is generally a feature, not a bug, of continuous delivery. It means fixes are getting out to users quickly. The CHANGELOG.md file provides a clean, readable summary that groups these changes by type.
Manual Intervention Needed	The automated system fails, and a release needs to be tagged manually.	The release manager should have the permissions and knowledge to perform the steps of semantic-release manually if absolutely necessary. The process should be documented. However, the primary goal should be to fix the automation, not to rely on the manual fallback.