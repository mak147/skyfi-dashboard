Document 07: Folder Structure
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the standard folder and file structure for both the Frontend (React SPA) and Backend (PHP REST API) codebases. A standardized structure is essential for a project with multiple developers to ensure that code is predictable, discoverable, and organized logically.

This structure is designed to enforce the architectural principles of separation of concerns and modularity, making the system easier to navigate, maintain, and scale.

2.0 Responsibilities
Role	Responsibility
Development Lead	Enforce adherence to this folder structure during code reviews. Ensure all new files and modules are placed in the correct location.
All Developers	Follow this structure when creating new files, components, and modules. Do not deviate without architectural approval.
CI/CD System	The structure will inform the paths used in build scripts, test runners, and deployment configurations.
3.0 Goals
Consistency: Provide a single, predictable way to organize files across the entire project.
Discoverability: Allow developers to quickly locate files and understand the system's layout without extensive prior knowledge.
Scalability: Create a structure that can accommodate a growing number of features, components, and modules without becoming chaotic.
Separation of Concerns: Physically separate code based on its role and responsibility (e.g., UI components, business logic, data access, configuration).
Alignment with Architecture: The folder structure must directly reflect the Modular Monolith and Component-Based UI architectures.
4.0 Guiding Principles
Feature-Based Organization: Where possible, code should be organized by feature or business domain (e.g., billing, crm) rather than by technical type (e.g., a single folder for all controllers). This is a key principle of the Modular Monolith.
Clear Naming Conventions: Files and folders should be named clearly and consistently, using camelCase for files and kebab-case for folders/components.
Top-Level Clarity: The root of each project should be clean and contain only essential configuration, entry points, and top-level directories.
5.0 Frontend Folder Structure (React / TypeScript)
The frontend will be organized to support a component-based architecture, with a clear distinction between shared/core logic and feature-specific components.

Directory Tree:

text

/frontend
├── .env                  // Environment variables (local, gitignored)
├── .eslintrc.cjs         // ESLint configuration
├── .gitignore            // Git ignore rules
├── index.html            // Main HTML entry point
├── package.json          // Project dependencies and scripts
├── postcss.config.js     // PostCSS configuration (for Tailwind CSS)
├── tailwind.config.js    // Tailwind CSS configuration
├── tsconfig.json         // TypeScript configuration
├── vite.config.ts        // Vite build tool configuration
│
├── public/               // Static assets that are copied directly
│   ├── favicon.ico
│   └── logo.svg
│
└── src/                  // Main application source code
    ├── main.tsx          // Main application entry point (renders App)
    │
    ├── assets/           // Static assets processed by Vite (images, fonts)
    │   ├── images/
    │   └── styles/
    │       └── index.css // Global styles and Tailwind directives
    │
    ├── components/       // *** SHARED & REUSABLE UI COMPONENTS ***
    │   ├── ui/           // Generic, unstyled base components (headless)
    │   │   ├── button.tsx
    │   │   ├── modal.tsx
    │   │   └── table.tsx
    │   └── common/       // Common application-specific components (e.g., PageHeader)
    │       ├── page-header.tsx
    │       └── data-table.tsx
    │
    ├── config/           // Application configuration (e.g., API endpoints)
    │   └── index.ts
    │
    ├── features/         // *** FEATURE-BASED MODULES (aligns with backend) ***
    │   ├── authentication/
    │   │   ├── api/        // API hooks for this feature (e.g., useLogin)
    │   │   ├── components/ // Components specific to this feature (e.g., LoginForm)
    │   │   ├── routes/     // Route definitions for this feature
    │   │   └── types.ts    // TypeScript types specific to this feature
    │   │
    │   ├── billing/
    │   │   ├── api/
    │   │   │   └── useInvoices.ts
    │   │   ├── components/
    │   │   │   └── invoice-list.tsx
    │   │   ├── routes/
    │   │   └── types.ts
    │   │
    │   └── crm/
    │       ├── api/
    │       ├── components/
    │       └── ...
    │
    ├── hooks/            // Shared, reusable React hooks (e.g., useLocalStorage)
    │   └── use-auth.ts
    │
    ├── lib/              // Shared utility functions, helpers, and API client setup
    │   ├── api.ts        // Axios/fetch instance setup
    │   ├── utils.ts      // General utility functions (date formatting, etc.)
    │   └── zod.ts        // Zod validation schemas
    │
    ├── providers/        // React Context providers (Theme, Auth, etc.)
    │   └── app-provider.tsx
    │
    ├── routes/           // Application-wide routing configuration
    │   ├── index.tsx     // Main router setup, combines feature routes
    │   └── protected-route.tsx // Wrapper for authenticated routes
    │
    └── store/            // Redux Toolkit store setup
        ├── hooks.ts      // Typed store hooks
        └── index.ts      // Store configuration
Architectural Justification:

src/features: This is the most critical directory. Organizing by feature (e.g., billing, crm) allows a developer to work on a single business capability in a self-contained area. It improves locality and cognitive load, directly supporting the modular architecture.
src/components/ui vs. src/components/common: This separation is intentional. ui contains highly generic, presentation-agnostic components (the "building blocks"). common contains components that are used across multiple features but have application-specific context (e.g., a standardized PageHeader that all pages use).
src/lib vs. src/hooks: lib is for pure TypeScript/JavaScript functions and class instances. hooks is specifically for custom React hooks that encapsulate logic and state within the React ecosystem.
API calls in features/: By co-locating TanStack Query hooks (useInvoices.ts) with the feature that uses them, we make it clear which API endpoints a feature depends on.
6.0 Backend Folder Structure (PHP REST API)
The backend will be organized around the Modular Monolith concept. Each business domain will have its own top-level directory within the source, containing all its specific logic.

Directory Tree:

text

/backend
├── .env                  // Environment variables (local, gitignored)
├── .gitignore            // Git ignore rules
├── composer.json         // PHP package dependencies
├── phpunit.xml           // PHPUnit test configuration
│
├── config/               // Application configuration files
│   ├── app.php
│   ├── database.php
│   ├── services.php      // Configuration for external services (Stripe, Twilio)
│   └── cors.php
│
├── database/             // Database-related files
│   ├── migrations/       // Schema migration files
│   ├── seeders/          // Database seeders for test/default data
│   └── factories/        // Model factories for testing
│
├── public/               // Public web server root
│   └── index.php         // *** SINGLE APPLICATION ENTRY POINT ***
│
├── routes/               // API route definitions
│   ├── api.php           // Main API route file
│   └── auth.php          // Authentication-specific routes
│
├── src/                  // *** MAIN APPLICATION SOURCE CODE ***
│   │
│   ├── Billing/          // === BILLING MODULE ===
│   │   ├── Contracts/    // Interfaces for services and repositories
│   │   ├── Controllers/  // HTTP controllers (InvoiceController)
│   │   ├── Data/         // Data Transfer Objects (DTOs)
│   │   ├── Events/       // Domain events (e.g., InvoiceGenerated)
│   │   ├── Listeners/    // Listeners for events
│   │   ├── Models/       // Database models (Invoice, Payment)
│   │   ├── Repositories/ // Data access logic
│   │   ├── Resources/    // API resource transformers (JSON structure)
│   │   ├── Rules/        // Custom validation rules
│   │   └── Services/     // Business logic services (BillingService)
│   │
│   ├── Crm/              // === CRM MODULE ===
│   │   ├── ...           // (Follows same structure as Billing)
│   │
│   ├── Network/          // === NMS MODULE ===
│   │   ├── Adapters/     // Adapters for different hardware (MikroTikAdapter)
│   │   ├── Contracts/
│   │   ├── ...           // (Follows same structure)
│   │
│   ├── Shared/           // === SHARED KERNEL & INFRASTRUCTURE ===
│   │   ├── Auth/         // JWT handling, guards
│   │   ├── Exceptions/   // Custom exception classes, global handler
│   │   ├── Http/         // Base controllers, middleware
│   │   ├── Providers/    // Service container providers
│   │   └── ...
│   │
│   └── Support/          // === SUPPORT MODULE ===
│       └── ...           // (Follows same structure)
│
├── storage/              // Framework-specific storage (cache, logs, etc.)
│   ├── cache/
│   ├── framework/
│   └── logs/
│
└── tests/                // Automated tests
    ├── Feature/          // Feature tests (testing endpoints)
    │   ├── Billing/
    │   └── ...
    ├── Unit/             // Unit tests (testing individual classes)
    │   ├── Billing/
    │   └── ...
    └── TestCase.php      // Base test case class
Architectural Justification:

src/Billing, src/Crm, etc.: This is the physical implementation of the Modular Monolith. Each top-level directory in src/ represents a distinct business capability. This structure allows a team to focus on the Billing module with minimal need to look inside the Crm directory.
Internal Module Structure (Controllers, Services, Repositories): This layered approach within each module separates concerns effectively:
Controllers: Handle HTTP request/response cycle only. They are thin and delegate to services.
Services: Contain the core business logic. They orchestrate models and repositories to fulfill a use case.
Repositories: Abstract the database layer. The service asks the repository for data without knowing if it comes from MySQL, a cache, or somewhere else.
Contracts (Interfaces): Using interfaces for services and repositories is crucial for loose coupling and testability. It allows for dependency injection and easy mocking in tests.
src/Shared: This directory is the application "kernel." It contains cross-cutting concerns like authentication, base classes, and exception handling that all modules depend on. Code should only be placed here if it is truly generic and used by multiple modules.
tests/ Mirroring src/: The test directory structure mirrors the src/ directory. This makes it trivial to find the tests for a specific class or module (e.g., tests for src/Billing/Services/BillingService.php are in tests/Unit/Billing/Services/BillingServiceTest.php).
7.0 Implementation Notes
Code Generation: It is highly recommended to use command-line tools (e.g., custom scripts or framework-provided commands) to scaffold new modules and classes. This ensures that the correct structure and boilerplate are created automatically, enforcing consistency.
Linting: ESLint (for frontend) and PHP-CS-Fixer/PHPStan (for backend) will be configured to enforce code style and catch structural issues automatically. A pre-commit hook should be used to run linters before code is even committed.
Monorepo vs. Polyrepo: This documentation assumes two separate repositories (frontend and backend). An alternative approach is a monorepo (using tools like pnpm workspaces or Nx), which would contain both projects in a single Git repository. This can simplify dependency management and cross-project changes but adds tooling complexity. The decision to use a monorepo is a tactical one, but the internal folder structure of each project would remain the same. For this project, a polyrepo (two repositories) is recommended for simplicity and clear separation of team concerns.
8.0 Risks
Risk	Description	Mitigation Strategy
Structure Decay	Over time, developers under pressure may place files in incorrect locations, leading to chaos.	Strict, automated enforcement. The Development Lead must be vigilant during code reviews. Running linters in the CI pipeline that fail the build on structural violations is essential.
Overly-Rigid Structure	A structure that is too complex or rigid can stifle productivity.	The proposed structure is based on well-established enterprise patterns. Regular architectural reviews should be held to assess if the structure still meets the team's needs and make adjustments if necessary.
"Shared" Abuse	The Shared backend directory becomes a dumping ground for code that isn't truly shared, leading to coupling.	A high bar must be set for adding code to Shared. It must be demonstrably used by at least two other top-level modules and be domain-agnostic.