Document 22: Validation Standards
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the standards for data validation across the entire SkyFi Networks application stack. It defines the layers of validation, the technologies used, the standard set of rules, and the format of error messages.

The purpose is to create a robust, multi-layered validation strategy that:

Ensures high data integrity and quality.
Provides a secure barrier against invalid or malicious data.
Delivers a clear and helpful user experience by providing immediate and understandable feedback.
Standardizes validation logic to reduce code duplication and improve maintainability.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Define the validation strategy and approve standard validation rules.
Backend Developers	Implement authoritative server-side validation for all API endpoints.
Frontend Developers	Implement client-side validation for immediate user feedback, using the same schema definitions where possible.
QA Engineers	Create test cases that specifically target validation rules, including edge cases and invalid data formats.
3.0 Validation Philosophy: Defense in Depth
Our validation strategy is based on a "defense-in-depth" or multi-layered approach. Validation is not a one-time check but a series of gates that data must pass through.

Layer 1: Frontend (Client-Side) Validation:

Purpose: User Experience (UX). Provide immediate feedback to the user as they fill out a form, preventing them from submitting invalid data.
Nature: This is a "soft" validation layer. It can be bypassed by a malicious user. It should never be trusted as the sole source of validation.
Layer 2: Backend (Server-Side) Validation:

Purpose: Security and Data Integrity. This is the authoritative validation layer. It re-validates all incoming data at the API boundary, assuming all client-side data is untrusted.
Nature: This is a "hard" validation layer. If data fails this check, it is rejected, and an error response is sent to the client. This is the system's single source of truth for data correctness.
Architectural Justification: This two-layer approach provides the best of both worlds. The user gets a fast, responsive UI that helps them avoid mistakes, while the backend is protected by a robust, secure gatekeeper that guarantees the integrity of any data entering the system's core logic.

4.0 Technology and Implementation
Layer	Technology	Implementation
Frontend	Zod	A Zod schema will be defined for every form. This schema will be passed to React Hook Form's zodResolver to automatically handle client-side validation.
Backend	PHP Framework Validation Component	The chosen PHP framework's built-in validation component (e.g., Laravel's Validator) will be used. This provides a rich set of rules and a consistent way to handle validation logic within API controllers.
Future Goal: Shared Schema
The ultimate enterprise goal is to have a single source of truth for validation schemas. A future project phase will involve creating a private package (e.g., via NPM/Composer) that contains Zod schemas defined in a language-agnostic format (like JSON Schema) or directly in TypeScript/Zod, from which both the PHP backend and React frontend can generate their respective validation rules. For v1.0, the schemas will be maintained separately but must be kept in sync.

5.0 Validation Rule Standards
These are common validation rules that must be applied consistently across the application.

5.1 Presence & Type
Required: All fields that are NOT NULL in the database must be marked as required.
Zod: .min(1, { message: 'This field is required.' }) for strings, .nonempty() for arrays.
PHP: required
Data Type: The type must match the expected format.
Zod: .string(), .number(), .email(), .url(), .uuid(), .boolean()
PHP: string, integer, numeric, boolean, array
5.2 String Formats
Email: Must be a valid email format.
Zod: .email()
PHP: email
Minimum/Maximum Length: Enforce character limits to match database column sizes and business rules.
Zod: .min(8), .max(255)
PHP: min:8, max:255
Specific Formats (Regex): For things like phone numbers or postal codes where a specific format is desired.
Zod: .regex(/.../)
PHP: regex:/.../
5.3 Numerical Formats
Integer: Must be a whole number.
Zod: .int()
PHP: integer
Range: Must fall between a minimum and maximum value.
Zod: .min(0), .max(100)
PHP: min:0, max:100
Currency: Must be a valid numeric format suitable for currency (e.g., max 2 decimal places).
Zod: .number().multipleOf(0.01)
PHP: numeric, decimal:0,2 (or similar depending on framework)
5.4 Date and Time
Valid Date: Must be a real, parsable date.
Zod: .datetime() or .date()
PHP: date
Temporal Order: Must be before or after another date field.
Zod: .refine() for custom cross-field validation.
PHP: before:field, after:field
5.5 Relational & Database Rules
These rules can typically only be enforced on the backend.

Unique: The value must be unique in a specific database table column.
PHP: unique:table,column (e.g., unique:customers,email)
Exists: The value (typically an ID) must exist as a primary key in another database table.
PHP: exists:table,column (e.g., exists:customers,id)
6.0 Error Message Standards
Validation error messages are part of the user experience. They must be clear, helpful, and consistent.

Clarity: Avoid technical jargon. Explain the error in plain language.
Bad: "Validation failed for field 'firstName'."
Good: "First name is required."
Helpfulness: Tell the user how to fix the problem.
Bad: "Invalid password."
Good: "Password must be at least 8 characters long and contain one number."
Consistency: Use a consistent tone and format for all messages.
Localization: All validation messages must be stored in language files to support future internationalization (i18n), even if only English is initially supported. Do not hardcode error strings.
API Error Response Format:
As defined in the API Architecture, validation errors from the backend will be returned with a 422 Unprocessable Entity status and a structured JSON body.

JSON

{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email has already been taken."
    ],
    "password": [
      "The password must be at least 8 characters.",
      "The password confirmation does not match."
    ]
  }
}
The frontend will parse this response and use the setError function from React Hook Form to display these messages next to the appropriate fields.

7.0 Implementation Workflow
7.1 Frontend

Define a Zod schema for the form data.
Create the inferred TypeScript type from the schema.
Initialize useForm with zodResolver(schema).
Use the FormField common component which will automatically display error messages from the form state.
7.2 Backend

In the API Controller method, create a validation rule set (e.g., a FormRequest class in Laravel).
The rules should mirror the Zod schema's intent.
The framework will automatically handle the validation. If it fails, it will generate and send the 422 response.
If validation passes, the controller method will proceed with executing the business logic.
8.0 Security Considerations
Never Trust the Client: Always assume that client-side validation has been bypassed. The backend validation is the only one that matters for security.
Sanitization vs. Validation: Validation ensures data is in the correct format; sanitization removes potentially harmful parts of the data. Input should be sanitized to prevent XSS attacks, typically when rendering user-provided content. Our frontend framework (React) automatically sanitizes against XSS when rendering text, but care must be taken if using dangerouslySetInnerHTML. On the backend, data should be properly escaped before being inserted into SQL queries (handled by the ORM/DBAL).
File Uploads: Validate file uploads rigorously on the backend. Check for file type (MIME type), size, and scan for malware if possible. Do not trust the file extension provided by the client.
IDOR Protection: When validating an ID (e.g., invoice_id), the validation should not just check exists:invoices,id. The authorization policy must also check that the authenticated user has permission to access that specific invoice.
9.0 Risks
Risk	Description	Mitigation Strategy
Validation Discrepancy	Frontend and backend validation rules are out of sync, leading to a poor UX where valid client-side data is rejected by the server.	This is the most common risk. A rigorous QA process is the primary mitigation for v1.0. For future versions, implementing a shared schema library is the definitive solution. Regular, manual audits of key forms should be conducted.
Incomplete Validation	A field or an entire endpoint on the backend is missing validation, creating a data integrity or security hole.	A strict code review process where every PR that introduces a new field or endpoint is checked for corresponding validation rules. Automated static analysis tools can also be configured to flag controller methods that accept input but don't have a validation step.
Confusing Error Messages	Users don't understand the validation errors and get frustrated or call support.	Create a "glossary" of standard error messages in the i18n files and reuse them. The UX designer should review all validation messages for clarity and consistency.