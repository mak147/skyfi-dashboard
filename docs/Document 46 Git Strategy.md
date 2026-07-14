Document 46: Git Strategy
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Mandated Standard

1.0 Purpose
This document specifies the Git strategy and associated best practices for the SkyFi Networks source code repositories. It defines standards for commits, branching, pull requests, and general repository hygiene.

The goal is to establish a clear, consistent, and professional workflow that:

Enables parallel development by multiple developers without conflict.
Maintains a clean, understandable, and useful project history.
Ensures code quality through mandatory code reviews.
Integrates seamlessly with our CI/CD and issue tracking systems.
Protects our main branches from unstable or broken code.
2.0 Responsibilities
Role	Responsibility
Development Lead	Enforce the Git strategy. Manage branch protections and approve pull request merges.
All Developers	Adhere strictly to the commit message format, branching model, and pull request process for all code contributions.
DevOps Engineers	Configure the Git provider (e.g., GitHub, GitLab) with branch protection rules and integrate it with the CI/CD pipeline.
3.0 Repository Structure
We will use a polyrepo approach:

skyfi-frontend: The React/TypeScript SPA.
skyfi-backend: The PHP REST API.
Justification: While a monorepo has its advantages, a polyrepo approach provides a cleaner separation of concerns for these two distinct applications. It allows for independent build/deployment pipelines, issue tracking, and versioning, which is simpler to manage for teams that may have different release cadences.

4.0 Branching Model
We will adopt the GitHub Flow model, which is a lightweight, branch-based workflow. It is simpler than GitFlow and is ideal for teams that practice continuous delivery.

Core Principles:

The main branch is always deployable. Anything in main is considered stable and can be released to production at any time.
All new work (features, bug fixes) is done on a descriptive feature branch created from main.
Work is pushed to the remote feature branch regularly to enable collaboration and visibility.
When the work is complete, a Pull Request (PR) is opened to merge the feature branch back into main.
After the PR is reviewed and approved, and after all automated checks have passed, it is merged into main.
Diagram of GitHub Flow:

mermaid

gitGraph
    commit id: "Initial"
    branch feature-A
    commit id: "feat(A): Start"
    commit id: "feat(A): Work"
    checkout main
    branch feature-B
    commit id: "fix(B): Start"
    checkout feature-A
    commit id: "feat(A): Finish"
    checkout main
    merge feature-A id: "Merge A"
    checkout feature-B
    commit id: "fix(B): Finish"
    checkout main
    merge feature-B id: "Merge B"
    commit id: "Release 1.0"
    tag: "v1.0.0"
5.0 Branch Naming Convention
Descriptive branch names are crucial for understanding what work is in progress.

Format: {type}/{issue-id}-{short-description}
Types:
feature/: For new features.
fix/: For bug fixes.
chore/: For maintenance tasks that don't change application logic (e.g., updating dependencies, CI/CD config).
refactor/: For code refactoring without changing external behavior.
docs/: For documentation changes.
Issue ID: The ID from our issue tracking system (e.g., Jira, Linear). This allows for automatic linking.
Examples:
feature/SFW-123-create-customer-form
fix/SFW-125-invoice-proration-error
chore/SFW-130-upgrade-react-router
6.0 Commit Message Standards
We will adhere to the Conventional Commits specification. This creates a clean, explicit, and machine-readable commit history. It is essential for automated versioning and changelog generation.

Format:
text

<type>(<scope>): <subject>

[optional body]

[optional footer]
<type>:
feat: A new feature.
fix: A bug fix.
build: Changes that affect the build system or external dependencies.
chore: Other changes that don't modify src or test files.
ci: Changes to our CI configuration files and scripts.
docs: Documentation only changes.
perf: A code change that improves performance.
refactor: A code change that neither fixes a bug nor adds a feature.
style: Changes that do not affect the meaning of the code (white-space, formatting, etc.).
test: Adding missing tests or correcting existing tests.
<scope> (optional): The module or part of the codebase affected (e.g., billing, auth, customers).
<subject>: A short, imperative-mood summary of the change (e.g., "add validation for email field," not "added validation").
Examples:

text

feat(billing): implement proration for plan upgrades

Adds the ProrationService and integrates it into the SubscriptionService
to handle mid-cycle plan changes accurately.

Fixes SFW-101
text

fix(auth): correct redirect loop on token expiration

The response interceptor was not correctly clearing the failed request
queue after a successful token refresh, leading to an infinite loop.
text

chore: update eslint and prettier dependencies
Enforcement: A commit-msg Git hook will be used to validate that all commit messages adhere to this format.

7.0 Pull Request (PR) Standards
Pull Requests are the heart of our code quality and review process.

Size: PRs should be small and focused. A PR should ideally represent a single, logical unit of work. Large, monolithic PRs are difficult and time-consuming to review effectively.
Title: The PR title should follow the Conventional Commits format.
Description: The PR description must be filled out using a standard template. The template will include:
Purpose: What is the goal of this PR?
Related Issue: A link to the ticket (e.g., "Closes SFW-123").
Changes Made: A summary of the implementation.
Testing Steps: How can a reviewer or QA engineer test these changes?
Screenshots/GIFs: For any UI changes.
Review Process:
A PR must be reviewed and approved by at least one other developer. For critical security or architectural changes, two approvals may be required.
The author of the PR is responsible for addressing all review comments.
CI Checks: A PR cannot be merged until all CI checks (linting, tests, static analysis, build) have passed successfully.
8.0 Branch Protection Rules
The main branch will be protected by rules configured in our Git provider (GitHub/GitLab).

Require Pull Request: Direct pushes to main are strictly forbidden. All changes must come through a PR.
Require Status Checks to Pass: The "Merge" button will be disabled until all CI checks are green.
Require Conversation Resolution: All review comments must be marked as resolved before merging.
Require Linear History: Merges will be performed using Squash and Merge or Rebase and Merge. This is a critical decision.
Decision: We will use Squash and Merge.
Justification: This practice condenses all the small, messy commits from a feature branch into a single, clean commit on the main branch. This keeps the main branch history incredibly clean and readable, with each commit representing a complete feature or fix. The PR itself preserves the detailed development history if needed.
9.0 Git Workflow Best Practices
Pull Before Pushing: Before pushing your feature branch, always pull the latest changes from the remote main branch and merge or rebase them into your branch (git pull origin main). This resolves conflicts on your local branch, not in the main branch.
Interactive Rebase: Before opening a PR, use interactive rebase (git rebase -i origin/main) to clean up your local commit history. Squash small "fixup" or "WIP" commits into larger, more meaningful commits that tell a story.
No Force Pushing to Shared Branches: Do not git push --force to any shared branch (main). It is acceptable to force-push to your own feature branch (e.g., after a rebase), but be aware that it can cause issues for any collaborators on that branch.
10.0 Risks
Risk	Description	Mitigation Strategy
Messy Git History	The main branch becomes cluttered with "WIP," "fix," and merge commits, making it impossible to read or revert changes.	The combination of Conventional Commits and the "Squash and Merge" branch protection rule completely mitigates this risk by ensuring the main history is pristine.
Merge Conflicts ("Merge Hell")	Long-lived feature branches diverge significantly from main, leading to massive and difficult merge conflicts.	Keep feature branches short-lived. Break large features down into smaller, independently shippable pieces. Pull from main into your feature branch frequently (at least daily).
Bypassing Process	A developer with admin privileges directly pushes a "hotfix" to main.	This is a discipline and access control issue. Admin/maintainer privileges on the repository should be limited. The process (create branch, open PR) is fast enough to handle even urgent fixes, and it ensures that all changes, even hotfixes, are tested and reviewed.
