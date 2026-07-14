Document 60: Developer Guidelines
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Living Document / Onboarding Guide

1.0 Purpose
This document provides a set of practical guidelines, best practices, and standard operating procedures for all developers working on the SkyFi Networks platform. It serves as a primary onboarding resource for new developers and a daily reference for the entire team.

The goal is to supplement the formal architectural documents with actionable "how-to" advice, ensuring that every developer understands the "SkyFi way" of building software. This promotes consistency, quality, and a smooth, collaborative development workflow.

2.0 Onboarding: The First Day
A new developer's first day should be productive. Their goal is to get the application running locally and successfully push a trivial change through the CI/CD pipeline.

Onboarding Checklist:

Access: Receive invitations to GitHub repositories, Jira/Linear project, and team communication channels (Slack).
Documentation Review: Read the following key documents before writing any code:
01: Executive Summary (to understand the "why")
05: Software Architecture
07: Folder Structure
45: Coding Standards
46: Git Strategy
Local Environment Setup:
Clone the skyfi-frontend and skyfi-backend repositories.
Install required local dependencies (Docker, Node.js, PHP, Composer).
Follow the README.md in each repository to set up the local environment (e.g., cp .env.example .env, composer install, npm install).
Run docker-compose up to start the local application stack.
Verify that you can access the frontend and backend locally and log in with seeded credentials.
"Hello World" Pull Request:
Create a new branch: chore/onboarding-{your-name}.
Make a trivial, non-breaking change (e.g., add your name to a "Contributors" list in a documentation file).
Commit the change using the Conventional Commits format.
Push the branch and open a Pull Request.
Observe the CI pipeline run, pass, and see your PR get approved and merged. This validates that your setup and access are working correctly.
3.0 The "Definition of Done" for a User Story
A user story or feature is not "done" when the code is written. It is done when it meets the following criteria:

 The code implements all acceptance criteria defined in the ticket.
 The code adheres to all architectural patterns and coding standards.
 The code is accompanied by meaningful Unit and/or Integration tests that cover the new logic. Code coverage for new business logic must meet the project's target (>80%).
 The code has been successfully peer-reviewed and approved in a Pull Request.
 All automated CI checks for the PR have passed.
 If the change involves a new feature, documentation for that feature (in-app or external) has been written or updated.
 The feature has been verified by a QA engineer on the Staging environment.
 Any new configuration or environment variables have been added to the Staging/Production secret stores.
4.0 Backend Development Guidelines
Architecture First: Always work within the established layered architecture (Controller -> Service -> Repository).
Controllers are for HTTP only. Keep them thin.
Services are for business logic. This is where most of your work should be.
Repositories are for data access only.
Fat Services, Thin Everything Else: Complex logic belongs in a service class. If your controller method is longer than 10-15 lines, you are probably putting business logic in the wrong place.
Use Events for Side Effects: If an action needs to trigger a secondary, unrelated action (like sending a notification or creating an audit log), dispatch an event. Do not call the NotificationService directly from the BillingService. This keeps your modules decoupled.
Type Everything: Use PHP's strict types for all properties, arguments, and return types. Trust the static analyzer (phpstan).
Validate on the Way In: All data coming from an external source (an API request) must be validated at the controller boundary. Never trust incoming data in your service layer.
Test Your Logic: Write unit tests for your service classes by mocking their dependencies. Write integration tests for your API endpoints to verify the entire flow.
5.0 Frontend Development Guidelines
Follow the State Management Strategy (Doc 19):
Is the data from the server? Use TanStack Query (useQuery, useMutation).
Is it global UI state (auth, theme)? Use Redux Toolkit.
Is it for a form? Use React Hook Form.
Is it local to one component? Use useState.
Do not put server data in the Redux store. Use queryClient.invalidateQueries to keep the UI in sync after mutations.
Build with Components: Break down UIs into small, reusable components. Follow the ui/ vs. common/ vs. features/ structure.
Think in Server State: Embrace the isLoading, isError, and data states provided by TanStack Query. Your UI must handle all three states gracefully (e.g., show a skeleton loader, an error message, or the final data).
URL is State: For any state that should be bookmarkable or shareable (filters, tabs, pagination), store it in the URL search parameters (useSearchParams). Your components should read from the URL, not the other way around.
Accessibility is Not Optional: Use semantic HTML. Ensure all interactive elements are keyboard accessible. Use ARIA attributes where necessary. Run accessibility checks locally.
Profile for Performance: Use the React DevTools Profiler to identify and fix unnecessary re-renders in complex components.
6.0 Code Review Etiquette
Code reviews are a critical part of our quality process and a tool for learning and collaboration, not for criticism.

For the Author:

Prepare your PR: Review your own code before submitting. Your PR description should be detailed and explain the "why" of your changes.
Be Open to Feedback: Your code is not you. Constructive feedback is intended to improve the product and the codebase.
Respond to Comments: Acknowledge every comment. If you disagree, explain your reasoning professionally. Mark comments as resolved once they have been addressed.
For the Reviewer:

Be Kind and Constructive: Start with positive feedback. Phrase suggestions as questions (e.g., "What do you think about abstracting this into a separate function?").
Review for a Higher Purpose: Don't just look for style errors (the linter should catch those). Look at the architecture, logic, potential edge cases, and test coverage. Does this code meet our standards?
Be Timely: Your teammates are blocked waiting for your review. Treat reviews with the same priority as your own development tasks.
Approve or Request Changes: Be explicit. If the PR is good to go, approve it. If it needs work, use the "Request Changes" feature and provide clear, actionable feedback.
7.0 Communication
Asynchronous First: Use the issue tracking system and PR comments as the primary means of communication about a specific piece of work. This creates a written record.
Synchronous for Blockers: If you are blocked or need to discuss a complex architectural issue, don't hesitate to start a huddle or a call. Summarize the outcome of the discussion in the relevant ticket afterward.
Demos: At the end of each sprint, be prepared to demonstrate the working software you built during the sprint review.
8.0 Final Word: Ownership and Professionalism
You are not just a coder; you are a professional software engineer and a part-owner of this platform. Take pride in your work. Write code that you would be proud for your peers to see. If you see something that is broken or could be improved, raise the issue. Proactively seek to improve not just the code, but our processes and our team as a whole. Every line of code contributes to the success or failure of SkyFi Networks. Let's build something excellent, together.

