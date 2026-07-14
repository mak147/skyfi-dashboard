Document 45: Coding Standards
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Mandated Standard

1.0 Purpose
This document defines the official coding standards and best practices for all source code written for the SkyFi Networks ISP Management System. It covers both the PHP backend and the React/TypeScript frontend.

The purpose is to establish a unified style guide that ensures the entire codebase is:

Consistent: Code written by any developer looks and feels the same.
Readable: Code is easy to understand, reducing the cognitive load for maintenance and debugging.
Maintainable: A clean, consistent style makes it easier to refactor and extend the codebase.
High-Quality: Adherence to standards reduces common bugs and improves overall software quality.
Compliance with these standards is mandatory and will be enforced through automated tools and code reviews.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Define and maintain the coding standards.
Development Leads	Enforce the standards during code reviews and mentor developers on best practices.
All Developers	Write all new code according to these standards. Proactively refactor existing code to meet standards when working in a file.
CI/CD System	Automatically run linters and static analysis tools to check for violations on every pull request.
3.0 General Principles
Clarity Over Cleverness: Write code that is simple and easy to understand. A "clever" one-liner that requires 10 minutes to decipher is worse than five lines of straightforward code.
Don't Repeat Yourself (DRY): Avoid duplicating code. Abstract common logic into reusable functions, services, or components.
Single Responsibility Principle (SRP): Every class, function, or component should have one, and only one, reason to change. Keep them small and focused.
Boy Scout Rule: Leave the code cleaner than you found it. If you work on a file that has style violations, take a few extra minutes to clean them up.
Comments for the "Why," Not the "What": The code should be self-documenting in what it does. Use comments to explain why a particular approach was taken, to clarify complex business logic, or to leave // TODO: or // FIXME: markers.
4.0 PHP (Backend) Standards
Style Guide: We will adhere to the PSR-12 (Extended Coding Style) standard. This is the community standard for modern PHP and covers naming conventions, declarations, control structures, and more.
Automated Tooling: PHP-CS-Fixer will be configured with the PSR-12 rule set and integrated into our CI pipeline and pre-commit hooks.
Static Analysis: PHPStan will be used for static analysis at a strict level (e.g., level 5 or higher). It will be configured to catch type errors, dead code, and potential bugs before the code is even run.
Key Highlights and Project-Specific Rules:

PHP Version: The project will target a modern, stable version of PHP (e.g., PHP 8.2+).
Strict Types: All PHP files must start with declare(strict_types=1);.
Type Hinting: All method arguments, return types, and class properties must have explicit type hints. Use mixed only when absolutely necessary and document why.
Naming Conventions:
Classes: PascalCase (e.g., BillingService).
Methods: camelCase (e.g., generateRecurringInvoice).
Variables: camelCase (e.g., $customerList).
Constants: UPPER_CASE_SNAKE_CASE (e.g., DEFAULT_CURRENCY).
Interfaces: Must end with Interface or Contract (e.g., PaymentGatewayContract).
Traits: Must end with Trait (e.g., AuditableTrait).
Exceptions: Must end with Exception (e.g., InvoiceNotFoundException).
Final by Default: Consider using the final keyword for classes that are not designed to be extended. This prevents unintentional inheritance and promotes composition.
Constructor Property Promotion: Use constructor property promotion for cleaner and more concise class definitions.
PHP

// Good
public function __construct(
    private readonly PaymentGatewayContract $paymentGateway,
) {}

// Bad
private $paymentGateway;
public function __construct(PaymentGatewayContract $paymentGateway) {
    $this->paymentGateway = $paymentGateway;
}
Dependency Injection: All dependencies must be injected via the constructor. Do not use service locators or global helpers to retrieve dependencies. Type hint against interfaces, not concrete classes.
Read-only Properties: Use readonly properties where applicable to create immutable objects and data structures.
PHPDoc Blocks: All public and protected methods must have a PHPDoc block describing what the method does, its parameters (@param), and its return value (@return).
5.0 TypeScript / React (Frontend) Standards
Style Guide: We will use the Airbnb JavaScript Style Guide as a base, adapted for TypeScript.
Automated Tooling:
ESLint: Will be configured with plugins for React (eslint-plugin-react), React Hooks (eslint-plugin-react-hooks), and TypeScript (@typescript-eslint/eslint-plugin).
Prettier: Will be used for automated code formatting. It will be configured to run on save and as a pre-commit hook. An ESLint plugin will be used to prevent conflicts between Prettier and ESLint rules.
Static Analysis: The tsc (TypeScript Compiler) with noEmit and strict: true in tsconfig.json is our primary static analysis tool. The CI build will fail if there are any TypeScript errors.
Key Highlights and Project-Specific Rules:

File Naming:
Components: PascalCase.tsx (e.g., CustomerForm.tsx).
Hooks: useCamelCase.ts (e.g., usePermissions.ts).
General files: camelCase.ts (e.g., apiClient.ts).
Styles/Config: kebab-case.css or kebab-case.js.
Component Definitions: Use function components with hooks. Class components are forbidden for new code.
TypeScript

// Good
interface MyComponentProps {
  title: string;
}
export const MyComponent: React.FC<MyComponentProps> = ({ title }) => {
  return <div>{title}</div>;
};
TypeScript Types:
Use interface for defining the shape of objects and component props.
Use type for defining unions, intersections, or simple type aliases.
Avoid the any type. Use unknown for data whose type is not known at compile time and perform runtime checks.
Folder Structure: Adhere strictly to the defined Folder Structure (Document 07). Components should be co-located with their feature.
Imports:
Organize imports into groups: 1. React, 2. External libraries, 3. Internal absolute paths (@/features/...), 4. Relative paths (../).
An ESLint rule will enforce this sorting automatically.
Use absolute path aliases (@/ pointing to src/) for imports outside the current module to avoid long, fragile relative paths (../../../../...).
State Management: Follow the State Management Strategy (Document 19) strictly. Do not mix state types in the wrong managers.
Styling:
Use Tailwind CSS utility classes directly in the JSX.
For complex, reusable sets of styles, a component variant can be created, but avoid creating custom CSS classes (@apply) where possible to maintain the utility-first methodology.
The clsx utility must be used for conditional class application.
Arrow Functions: Use arrow functions for all function expressions and callbacks.
Destructuring: Use destructuring for props and objects to improve readability.
TypeScript

// Good
const { user, isLoading } = useAuth();

// Bad
const auth = useAuth();
const user = auth.user;
const isLoading = auth.isLoading;
6.0 Enforcement Workflow
IDE Configuration: Project-level settings files for VS Code will be included in the repository, recommending extensions for ESLint, Prettier, and PHP Intelephense/PHP CS Fixer.
Pre-commit Hook: A Git hook (managed by husky) will be configured to run prettier --write and eslint --fix (or the PHP equivalent) on all staged files before a commit is allowed. This ensures that no poorly formatted code enters the repository.
CI Pipeline:
The CI pipeline will run the linters and static analysis tools (eslint, tsc --noEmit, php-cs-fixer --dry-run, phpstan) on every pull request.
If any violations are found, the build will fail, blocking the PR from being merged.
Code Review: Automated tools catch style and syntax. Human reviewers focus on higher-level concerns: Is the logic correct? Does it follow the architectural patterns? Is it readable and maintainable? Is there a better approach?
7.0 Risks
Risk	Description	Mitigation Strategy
Tooling Overhead	Developers find the linters and hooks to be slow or annoying, leading them to bypass the checks.	The configuration of the tools must be optimized for speed. Hooks should only run on staged files, not the entire codebase. The value of the tools in catching bugs and enforcing consistency must be clearly communicated.
"Style Over Substance"	Code reviews devolve into arguments about minor style preferences instead of focusing on the logic.	This is why automated formatters like Prettier are essential. They take style out of the equation. The team agrees on the rules once, configures the tool, and the tool becomes the sole arbiter of style. This frees up human reviewers to focus on what matters.
Inconsistent Adoption	Some developers follow the standards while others do not, leading to a fragmented and inconsistent codebase.	The CI pipeline is the ultimate gatekeeper. If the code does not pass the automated checks, it cannot be merged. This makes adherence non-negotiable.