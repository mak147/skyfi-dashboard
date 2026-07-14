Document 56: QA Process
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Guiding Standard

1.0 Purpose
This document specifies the formal Quality Assurance (QA) process for the SkyFi Networks platform. It outlines the step-by-step activities, roles, and quality gates that ensure a feature is ready for release. This process integrates with our Agile/Scrum development methodology.

The goal is to establish a predictable, repeatable, and rigorous process that systematically verifies the quality of our software, minimizes the risk of production defects, and ensures each release meets the company's high standards.

2.0 Responsibilities
Role	Responsibility
QA Lead	Owns and facilitates the QA process. Makes the final "go/no-go" recommendation for a release based on test results.
QA Engineers	Execute the QA plan for each sprint/release, including test case execution, exploratory testing, and regression testing. Create and manage bug reports.
Developers	Participate in the QA process by fixing identified bugs, providing technical context to QA, and performing peer code reviews.
Product Owner	Participate in User Acceptance Testing (UAT). Validate that the implemented features meet the business requirements and acceptance criteria.
DevOps Engineers	Ensure the stability and availability of the Staging environment where all QA activities take place.
3.0 QA in the Development Lifecycle (Agile/Scrum)
QA is not a separate phase at the end of development; it is an integrated part of every sprint.

Workflow within a Sprint:

mermaid

flowchart LR
    A[Sprint Planning] --> B{User Story with<br>Acceptance Criteria}
    
    subgraph "Development"
        C[Developer starts work]
        C --> D[Writes code]
        D --> E[Writes Unit & Integration Tests]
        E --> F[Opens Pull Request]
    end

    subgraph "CI & Code Review"
        F --> G{Peer Code Review}
        G --> H{Automated CI Pipeline Runs}
        H --> I{Tests & Scans Pass?}
        I -- Yes --> J[Merge to `main`]
        I -- No --> C
    end
    
    subgraph "QA Process"
        J --> K[Auto-deploy to Staging]
        K --> L[QA Engineer verifies User Story<br>on Staging]
        L --> M{Bug Found?}
        M -- Yes --> N[Create Bug Ticket]
        N --> C
        M -- No --> O[Mark User Story as "Done"]
    end

    B --> L

    style K fill:#cde4ff
Key Principles:

"Shift Left": Quality is built in from the beginning. QA participates in sprint planning to ensure user stories have clear, testable acceptance criteria.
Continuous Testing: The automated CI pipeline provides the first layer of QA on every single commit.
Verification on Staging: The Staging environment is the primary ground for all formal QA activities. A feature is not considered "Done" until it has been verified by a QA engineer on Staging.
4.0 The Formal QA Cycle for a Release
While individual stories are tested continuously, a more formal QA cycle is initiated when a collection of features is ready for a production release.

Phase 1: Test Planning

Trigger: At the start of a release cycle (or sprint).
Activities:
The QA Lead reviews the planned features and user stories.
A Test Plan is created for the release, outlining:
Scope: What features are in-scope and out-of-scope for testing.
Testing Types: The specific types of testing to be performed (e.g., regression, performance, E2E).
Environments: The specified Staging environment and its required configuration.
Resources: The QA engineers assigned.
Risks & Assumptions.
QA Engineers write new test cases for the new features in our Test Case Management tool (e.g., TestRail, Zephyr).
Phase 2: Test Execution (on Staging)

Trigger: A release candidate build is deployed to the Staging environment.
Activities:
Smoke Testing: A small set of automated or manual tests is run immediately after deployment to verify that the core functions of the application are working and the environment is stable. If smoke tests fail, the build is rejected immediately.
New Feature Testing: QA engineers execute the new test cases written for the release, systematically testing each feature against its acceptance criteria.
Regression Testing:
Automated: The full suite of automated E2E tests is run against the Staging environment. This is our primary defense against regressions.
Manual: QA engineers manually execute a predefined set of test cases covering the most critical, high-risk areas of the application that are not covered by automation.
Exploratory Testing: QA engineers spend time using the application "off-script," trying to find unexpected bugs and usability issues.
Defect Logging: All found issues are logged as bug tickets following the Defect Management process (Doc 55).
Phase 3: Triage and Bug Fixes

Trigger: As bug tickets are created.
Activities:
A Triage Meeting is held daily during the QA cycle, attended by the QA Lead, Development Lead, and Product Owner.
Each new bug is reviewed, prioritized, and assigned to a developer.
Developers work on fixing the bugs. Fixes are deployed to Staging via the standard PR process.
Verification: The QA engineer who reported the bug is responsible for verifying the fix on Staging and closing the ticket.
Phase 4: Release Sign-Off

Trigger: The planned test execution is complete, and the number of open critical/high-priority bugs is at or below the agreed-upon threshold.
Activities:
The QA Lead prepares a QA Sign-Off Report, which includes:
A summary of test execution (e.g., 950/1000 test cases passed).
A list of all automated test suite results.
A list of all known open bugs that will be in the release, along with their severity and justification for not being blockers.
A final "go" or "no-go" recommendation for the release.
This report is presented to the key stakeholders (Product Owner, Dev Lead, Executive) in a Release Go/No-Go Meeting.
If a "go" decision is made, the build is approved for production deployment.
QA Cycle Flow Diagram:

mermaid

graph TD
    A[Release Candidate Deployed to Staging] --> B(QA: Smoke Test);
    B -- Pass --> C(QA: Execute Test Plan);
    B -- Fail --> H(Reject Build);
    C --> D{Find Bugs?};
    D -- Yes --> E(Log Bug Tickets);
    E --> F(Dev: Fix Bugs);
    F --> G(Deploy Fix to Staging);
    G --> C;
    D -- No --> I(All Tests Executed);
    I --> J{Meet Exit Criteria?};
    J -- No --> E;
    J -- Yes --> K(QA Lead: Prepare Sign-Off Report);
    K --> L(Go/No-Go Meeting);
    L -- "Go" --> M[Approve for Production Deployment];
    L -- "No-Go" --> E;
5.0 User Acceptance Testing (UAT)
Purpose: To give business stakeholders (who are not on the dev or QA team) a chance to validate that the system meets their business needs.
Placement: UAT runs in parallel with the main QA cycle on the Staging environment.
Process:
The Product Owner identifies key stakeholders for the new features (e.g., a Finance Manager for a new billing report).
These stakeholders are given access to the Staging environment and a set of UAT scenarios to execute.
Feedback from UAT is logged. Critical issues are treated as high-priority bugs, while minor feedback may be logged as new user stories for a future sprint.
Formal sign-off from the Product Owner is required as part of the exit criteria.
6.0 Exit Criteria (Example)
A release is ready for sign-off when:

100% of "critical" path test cases have been executed and have passed.
The automated E2E test suite has a >98% pass rate.
There are zero open bugs with Severity = Blocker or Critical.
There are fewer than 5 open bugs with Severity = High.
Product Owner has provided UAT sign-off.
7.0 Risks
Risk	Description	Mitigation Strategy
QA as a Bottleneck	The QA cycle takes too long, delaying releases and creating a backlog of features waiting to be tested.	Heavy investment in automation. The more regression testing that can be reliably automated, the more time QA engineers have to focus on complex new features and exploratory testing. Parallelizing test execution in the CI/CD pipeline also helps.
Environment Discrepancy	The Staging environment does not perfectly match Production, causing bugs to be missed in QA that appear in Production.	Infrastructure as Code (IaC) is the mitigation. Both Staging and Production environments must be provisioned using the same Terraform scripts. The only differences should be in scale and configuration variables, not underlying architecture.
Inadequate Test Coverage	The test plan misses a critical area of the application, and a major regression bug slips through to production.	The test plan must be peer-reviewed. Code coverage metrics from unit tests provide one layer of insight. A "risk-based" testing approach should be used, where more testing effort is focused on the most complex and critical parts of the system (like billing and provisioning).
Subjective Bug Reporting	Developers and QA argue over the severity or reproducibility of a bug.	A clear, objective set of definitions for severity (Blocker, Critical, High, Low) must be established and agreed upon by all teams. All bug reports must include clear, precise steps to reproduce. "It doesn't work" is not an acceptable bug report.