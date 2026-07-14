Document 23: Error Handling Standards
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the standards for handling errors and exceptions throughout the SkyFi Networks application stack. It provides a comprehensive strategy for classifying errors, handling them at different architectural layers, and presenting them to users and developers in a meaningful way.

The goal is to create a system that is resilient, debuggable, and secure, ensuring that:

Users are never shown raw, technical error messages.
Developers receive detailed, actionable information to diagnose and fix issues.
The system fails gracefully and predictably.
All critical errors are logged and monitored.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Define the error handling architecture and custom exception hierarchy.
Backend Developers	Implement try/catch blocks, throw appropriate custom exceptions, and configure the global exception handler.
Frontend Developers	Implement logic to handle API error responses, display user-friendly error messages, and report client-side errors.
Operations / DevOps	Configure and monitor the external error tracking and alerting services.
3.0 Error Handling Philosophy
Fail Fast, Fail Gracefully: The system should identify an error state as early as possible. When an unrecoverable error occurs, the process should stop and provide a clear response, rather than continuing with potentially corrupt data.
Classify and Conquer: Not all errors are equal. We will classify errors to determine the appropriate response: Is it a user's fault (validation error), a predictable system issue (payment gateway down), or a truly unexpected bug?
User-Centric Feedback: Users should see simple, helpful messages that tell them what happened and what they can do next. They should never see stack traces, database errors, or other technical details.
Developer-Centric Logging: Developers need the opposite: rich, detailed, contextual information to understand the root cause of an error. All unexpected errors must be logged with a full stack trace and request context.
Centralized Handling: Use global exception handlers to catch all unhandled exceptions. This ensures that no error slips through the cracks and provides a consistent way to log and report failures.
4.0 Error Classification
We will classify exceptions into three main categories:

| Category | Description | Example | User Action | System Action |
| :--- | :--- | :--- | :--- |
| User Errors | Errors caused by invalid user input. These are predictable and recoverable. | A user submits a form with an invalid email; a user tries to access a page they don't have permission for. | Inform: Display a clear message on the UI explaining the problem (e.g., "Invalid email address"). | Reject: Return a 4xx HTTP status code (e.g., 422, 403) with a structured error body. Do not log as a system error. |
| Operational Errors | Known, predictable failures in the system or its dependencies. The application is working correctly, but an external factor has failed. | The payment gateway API is down; a MikroTik router is unreachable; an email service fails to send an email. | Inform & Retry: Display a message like "Could not process payment at this time. Please try again later." | Log & Alert (Warning): Log the event as a warning. If it persists, trigger an alert to the operations team. May implement retry logic with exponential backoff. |
| Programmer Errors (Bugs)| Unexpected, unhandled exceptions in the code. These represent a bug that needs to be fixed. | Null pointer exception; invalid method call; uncaught database constraint violation. | Apologize & Guide: Display a generic, friendly error page: "Oops! Something went wrong. Our team has been notified." Provide a unique error ID for support reference. | Log & Alert (Critical): Capture the full exception, stack trace, and request context. Send immediately to an error tracking service (e.g., Sentry, Bugsnag). Trigger a high-priority alert to the development team. |

5.0 Backend Error Handling (PHP API)
5.1 Custom Exception Hierarchy

We will create a set of custom exceptions that extend the base \Exception class. This allows us to catch specific types of errors and handle them differently.

mermaid

classDiagram
    direction TB
    class Exception {
        <<PHP Base>>
    }
    class AppException {
        <<Abstract>>
        +getHttpStatusCode()
        +getUserMessage()
    }
    class ValidationException {
        <<AppException>>
    }
    class AuthorizationException {
        <<AppException>>
    }
    class NotFoundException {
        <<AppException>>
    }
    class ExternalServiceException {
        <<AppException>>
    }
    Exception <|-- AppException
    AppException <|-- ValidationException
    AppException <|-- AuthorizationException
    AppException <|-- NotFoundException
    AppException <|-- ExternalServiceException
AppException: The base class for all our custom, "expected" exceptions.
ValidationException: Thrown when business logic validation fails within a service. Corresponds to a 422 response.
AuthorizationException: Thrown by policies when a user is not permitted to perform an action. Corresponds to a 403 response.
NotFoundException: Thrown by services when a requested resource doesn't exist. Corresponds to a 404 response.
ExternalServiceException: Thrown when an API call to a third party (Stripe, MikroTik) fails. Corresponds to a 503 Service Unavailable or a similar 5xx code.
5.2 Global Exception Handler

A single, centralized exception handler will be registered at the application's entry point. This handler is the safety net that catches everything.

Logic Flow:

mermaid

flowchart TD
    A[Exception is thrown] --> B{Is it an instance of `AppException`?}
    
    B -- Yes --> C[Get HTTP status & user message from the exception]
    C --> D{Is status code >= 500?}
    D -- Yes --> E[Log as Warning (e.g., ExternalServiceException)]
    D -- No --> F[Do not log (e.g., ValidationException)]
    E --> G
    F --> G[Format structured JSON error response]
    G --> H[Send HTTP Response]

    B -- No (Programmer Error) --> I[Generate a Unique Error ID (e.g., UUID)]
    I --> J[Log as CRITICAL to Error Tracker (Sentry)]
    J --> K[Format generic JSON error response with Error ID]
    K --> H
Justification: This architecture ensures that "User Errors" (4xx) don't pollute our error logs, while "Operational" and "Programmer" errors (5xx) are always logged with the correct severity. The custom exception hierarchy makes the code self-documenting and the handler's logic clean.

6.0 Frontend Error Handling (React SPA)
6.1 API Client Interceptor

The axios (or other API client) instance will have a global response interceptor to handle API errors centrally.

Logic:

The interceptor catches any response with a non-2xx status code.
For 401 Unauthorized: Triggers the refresh token flow (as defined in Doc 13). If refresh fails, redirect to login.
For 403 Forbidden: Redirects the user to a generic /403-access-denied page.
For 404 Not Found: Redirects the user to a generic /404-not-found page.
For 422 Unprocessable Entity: This is typically handled locally by the form submission logic, which uses the error details to populate form field errors. The global handler can ignore this.
For 5xx Server Errors:
Display a global "toast" notification: "A server error occurred. Please try again later."
Show a more prominent error message on the specific component that made the request.
The interceptor re-throws the error so that local try/catch blocks or useMutation's onError callback can perform component-specific actions if needed.
6.2 React Error Boundaries

Purpose: To catch rendering errors within the React component tree. These are client-side "Programmer Errors."
Implementation: We will create a generic ErrorBoundary component.
Placement:
One ErrorBoundary will wrap the entire application to catch any uncaught rendering error and display a generic "Oops!" page.
Smaller, more specific ErrorBoundary components can be placed around discrete parts of the UI (like a complex data visualization widget) to prevent a single failing component from crashing the entire page.
Logging: When an error boundary is triggered, it will log the error and component stack trace to our external error tracking service (Sentry).
6.3 User-Facing Error Display

Form Field Errors: Displayed inline, directly below the input field. Handled by the FormField component.
Toast Notifications: Used for non-blocking feedback (e.g., "Failed to save settings, please try again.").
Alert Components: Used for page-level or form-level errors that are not specific to a single field (e.g., "The credit card was declined.").
Full Page Errors: Used for critical, unrecoverable states (404, 403, generic 500 error). These pages will have a consistent design, a user-friendly message, and a link to return to the dashboard.
7.0 External Error Tracking Service
Tool: Sentry (or a similar service like Bugsnag, Rollbar).
Integration:
Backend: The PHP SDK will be integrated with the global exception handler.
Frontend: The React SDK will be initialized at the application's entry point. It will automatically catch unhandled promise rejections and errors captured by ErrorBoundary components.
Configuration:
Source maps for both frontend and backend will be uploaded to Sentry on each deployment. This is critical for de-obfuscating and viewing readable stack traces.
The user's ID and role will be associated with each error report for better context.
Release versioning will be used to track when errors are introduced or fixed.
Alerting rules will be configured to send notifications to Slack/email for new or high-frequency critical errors.
8.0 Risks
Risk	Description	Mitigation Strategy
Swallowing Errors	A developer writes an empty catch {} block, which hides an error and causes unpredictable behavior later on.	This is a major anti-pattern. Code reviews must be vigilant in catching this. A linting rule can be configured to flag empty catch blocks. The rule should be: "Always re-throw, handle, or log an exception."
Exposing Sensitive Information	A production error message leaks a database password, API key, or internal file path to the user.	The global exception handler is the primary defense. It ensures that for any unexpected error, a generic response is always sent. Production environments must have detailed error reporting (display_errors) turned off.
Alert Fatigue	The error tracking service is too noisy, causing developers to ignore alerts.	Fine-tune the alerting rules. Use proper error classification so that 4xx user errors and expected operational errors do not trigger critical alerts. Use Sentry's "ignore" and "rate limiting" features.
Client-Side Error Storm	A bug in the frontend JS causes thousands of identical errors to be sent to the tracking service from many users at once.	The error tracking service's SDK provides client-side rate-limiting and de-duplication to prevent this.