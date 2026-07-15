# Finance & Accounting Implementation Plan

## 1. Implementation Plan
- **Phase 1: Foundation (Database & Models)**
  - Create the directories for the `Finance` module in `backend/src/Finance/`.
  - Design and create database schema for Accounting.
  - Set up Models and DTOs for `ChartOfAccount`, `FinancialAccount`, `JournalEntry`, `JournalEntryLine`, `Transaction`.
- **Phase 2: Core Services (Backend API)**
  - Implement `ChartOfAccountsService` and `AccountsService` for CRUD operations.
  - Implement `JournalingService` to handle balanced double-entry logic.
  - Implement `LedgerService` for General Ledger operations.
  - Implement `RevenueService` and `ExpenseService` for managing operational cash flows.
  - Build required Controllers, Routes, and request Validators.
- **Phase 3: Frontend Implementation**
  - Create `frontend/src/features/finance/` and build pages: Dashboard, Accounts, Transactions, Revenue, Expenses, Journal Entries, General Ledger, Cash Management, Bank Accounts.
  - Develop reusable UI components (e.g., `AccountTable`, `JournalEntryForm`).
- **Phase 4: Integrations**
  - Connect with the Payment and Billing modules.
  - Listen to `payment.completed`, `payment.reversed`, `payment.refunded` events.
  - Process invoices and automatically post journal entries (Debit A/R, Credit Revenue).
- **Phase 5: RBAC & Testing**
  - Define and seed new permissions.
  - Protect frontend routes, UI elements, and API endpoints.
  - PHP syntax validation and frontend build/lint testing.

## 2. Database Schema

- **chart_of_accounts**:
  - `id` (INT, PK)
  - `account_number` (VARCHAR, UK)
  - `name` (VARCHAR)
  - `type` (ENUM: asset, liability, equity, revenue, expense)
  - `normal_balance` (ENUM: debit, credit)
  - `parent_id` (INT, FK)
  - `created_at`, `updated_at`

- **financial_accounts**:
  - `id` (INT, PK)
  - `account_type` (ENUM: cash, bank, merchant)
  - `name` (VARCHAR)
  - `chart_of_account_id` (INT, FK)
  - `balance` (DECIMAL)
  - `currency` (VARCHAR)
  - `status` (ENUM: active, inactive)
  - `created_at`, `updated_at`, `deleted_at`

- **journal_entries**:
  - `id` (BIGINT, PK)
  - `transaction_id` (UUID, to group lines)
  - `description` (VARCHAR)
  - `transaction_date` (DATE)
  - `source_id` (BIGINT, Poly FK)
  - `source_type` (VARCHAR, Poly FK)
  - `created_by` (INT, FK)
  - `created_at`

- **journal_entry_lines**:
  - `id` (BIGINT, PK)
  - `journal_entry_id` (BIGINT, FK)
  - `account_id` (INT, FK)
  - `debit_amount` (DECIMAL)
  - `credit_amount` (DECIMAL)

- **general_ledger**:
  - `account_id` (INT, PK, FK)
  - `balance` (DECIMAL)
  - `last_updated_at` (TIMESTAMP)

- **expenses & revenue (Operational tables for simple entry)**:
  - `id` (BIGINT, PK)
  - `category` (VARCHAR)
  - `amount` (DECIMAL)
  - `transaction_date` (DATE)
  - `description` (TEXT)
  - `financial_account_id` (INT, FK)
  - `chart_of_account_id` (INT, FK)
  - `created_by` (INT, FK)
  - `created_at`, `updated_at`

## 3. API Endpoint List
**Accounts:**
- `GET /api/finance/accounts`
- `POST /api/finance/accounts`
- `PUT /api/finance/accounts/{id}`
- `DELETE /api/finance/accounts/{id}`

**Chart of Accounts:**
- `GET /api/finance/chart-of-accounts`
- `POST /api/finance/chart-of-accounts`

**Journal Entries:**
- `GET /api/finance/journal-entries`
- `POST /api/finance/journal-entries`

**General Ledger:**
- `GET /api/finance/ledger`

**Revenue & Expenses:**
- `GET /api/finance/expenses`
- `POST /api/finance/expenses`
- `GET /api/finance/revenue`
- `POST /api/finance/revenue`

**Cash & Bank Management:**
- `POST /api/finance/transfer`

**Dashboard:**
- `GET /api/finance/dashboard`

## 4. Frontend Component Structure
```
frontend/src/features/finance/
├── components/
│   ├── AccountTable.tsx
│   ├── BalanceCards.tsx
│   ├── ExpenseForm.tsx
│   ├── FinancialStatistics.tsx
│   ├── JournalEntryForm.tsx
│   ├── RevenueCard.tsx
│   ├── TransactionTable.tsx
│   └── CashSummary.tsx
├── pages/
│   ├── AccountsPage.tsx
│   ├── BankAccountsPage.tsx
│   ├── CashManagementPage.tsx
│   ├── ExpensesPage.tsx
│   ├── FinanceDashboard.tsx
│   ├── GeneralLedgerPage.tsx
│   ├── JournalEntriesPage.tsx
│   └── RevenuePage.tsx
└── types/
    └── index.ts
```

## 5. RBAC Permission Mapping
- `finance.view` -> Allowed to view dashboard, read ledger, read accounts, read transactions.
- `finance.create` -> Allowed to create journal entries.
- `finance.update` -> Allowed to update/reverse entries.
- `finance.delete` -> Allowed to softly delete non-system entries.
- `finance.manage` -> Full access to Chart of Accounts and administrative finance settings.
- `finance.reports` -> View and export finance reports.
- `expenses.manage` -> Manage expenses operations.
- `revenue.manage` -> Manage revenue operations.
- `accounts.manage` -> Create and manage financial bank/cash accounts.

## 6. Billing Integration
- **Revenue Recognition:** We will hook into the invoice generation flow. When an invoice is finalized/issued, an event/call will be sent to the Finance Module to post a journal entry:
  - *Debit*: Accounts Receivable
  - *Credit*: Service Revenue

## 7. Payments Integration
- **Payment Lifecycle:** We will read from `payment_financial_events` (or implement an event listener) to trigger journal entries automatically:
  - Payment Completed: *Debit* Cash/Bank Account, *Credit* Accounts Receivable.
  - Payment Reversed: Reverse the above.
  - Payment Refunded: *Debit* Accounts Receivable, *Credit* Cash/Bank Account.
