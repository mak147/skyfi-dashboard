Document 27: Finance Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the Finance module. This module extends beyond operational billing to provide a structured, double-entry accounting framework. It is designed to track all financial movements within the system, manage the General Ledger, and generate standard financial reports.

The goal is to create an auditable, compliant, and integrated financial system that provides a complete picture of the company's financial health, moving beyond simple revenue collection into true financial management.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the double-entry accounting model, ledger structure, and reporting mechanisms.
Backend Developers	Implement the JournalingService, LedgerService, and reporting queries.
Finance Department / CPA	Critical Stakeholders. Define the Chart of Accounts. Validate that the system's transaction handling and reporting adhere to Generally Accepted Accounting Principles (GAAP).
QA Engineers	Develop tests to ensure that every financial event correctly generates balanced journal entries and that reports accurately reflect the ledger.
3.0 Core Principles: Double-Entry Accounting
The entire Finance architecture is built upon the principles of double-entry bookkeeping. This is a non-negotiable, foundational requirement for any serious financial system.

Every Transaction has Two Sides: Every financial event will result in at least one debit and one credit entry in different accounts.
The Equation Must Balance: For every transaction, the total value of debits must equal the total value of credits (Assets = Liabilities + Equity).
Audit Trail is King: The General Ledger, composed of immutable journal entries, is the ultimate, auditable source of financial truth. It can be replayed to reconstruct the financial state at any point in time.
Separation from Operations: The accounting layer (Journals, Ledgers) is separate from the operational layer (Invoices, Payments). Operational events trigger accounting entries, but the accounting records themselves are a distinct, immutable system.
Architectural Justification: A double-entry system provides immense benefits:

Error Detection: Unbalanced entries immediately signal an error.
Auditability: Every number on a financial statement can be traced back to its originating journal entries.
Compliance: It is the standard required for financial audits and reporting.
4.0 Finance Module Architecture
The Finance module will be event-driven. It listens for events dispatched by other modules (like Billing) and translates them into accounting transactions.

Event-Driven Workflow Diagram:

mermaid

graph TD
    subgraph "Operational Modules"
        A[BillingService dispatches `InvoiceGenerated`]
        B[PaymentService dispatches `PaymentReceived`]
        C[BillingService dispatches `CreditIssued`]
    end

    subgraph "Finance Module"
        subgraph "Event Listeners"
            L1[HandleInvoiceGenerated]
            L2[HandlePaymentReceived]
            L3[HandleCreditIssued]
        end
        
        subgraph "Core Finance Services"
            JS[JournalingService]
            LS[LedgerService]
            RS[ReportingService]
        end
    end

    subgraph "Finance Data Layer"
        COA[(Chart of Accounts)]
        JE[(Journal Entries)]
        GL[(General Ledger)]
    end

    A --> L1
    B --> L2
    C --> L3

    L1 --> JS
    L2 --> JS
    L3 --> JS

    JS -- "Creates balanced entries" --> JE
    JS -- "Posts entries to" --> GL

    RS -- "Reads from" --> GL
    RS -- "Uses" --> COA
Explanation: The BillingService knows nothing about accounting. It just announces, "An invoice was generated." The HandleInvoiceGenerated listener, owned by the Finance module, catches this event and instructs the JournalingService to create the appropriate debit and credit entries. This maintains a clean separation of concerns.

5.0 Key Data Models
5.1 chart_of_accounts
This is the foundational list of all financial accounts in the system. It is hierarchical.

Column	Type	Description
id	INT	PK
account_number	VARCHAR(20)	UK: The formal account number (e.g., "1100").
name	VARCHAR(100)	The account name (e.g., "Accounts Receivable").
type	ENUM(...)	asset, liability, equity, revenue, expense.
normal_balance	ENUM('debit', 'credit')	The side that increases the account's balance.
parent_id	INT	FK: For creating hierarchical accounts.
5.2 journal_entries
This is the immutable log of all transactions. It is an append-only table.

Column	Type	Description
id	BIGINT	PK
transaction_id	UUID	A unique ID grouping all entries for a single business transaction.
account_id	INT	FK: The account being affected.
description	VARCHAR(255)	A description of the transaction.
debit_amount	DECIMAL(13,2)	The debit value. NULL if it's a credit entry.
credit_amount	DECIMAL(13,2)	The credit value. NULL if it's a debit entry.
transaction_date	DATE	The date the business transaction occurred.
source_id	BIGINT	Polymorphic FK to the source record (e.g., Invoice ID).
source_type	VARCHAR	Polymorphic FK type (App\Models\Invoice).
5.3 general_ledger
This table stores the running balance for each account. It can be recalculated from journal_entries but is maintained for performance.

Column	Type	Description
account_id	INT	PK, FK
balance	DECIMAL(15,2)	The current balance of the account.
last_updated_at	TIMESTAMP	When the balance was last updated.
6.0 Core Finance Services
6.1 JournalingService

Responsibility: To create balanced, double-entry transactions.
Key Method: createTransaction(transaction_id, date, description, entries: array).
Logic:
Receives an array of debit/credit entries (e.g., [['account' => 1100, 'debit' => 50.00], ['account' => 4000, 'credit' => 50.00]]).
Validates that total debits equal total credits. If not, throws a TransactionImbalanceException.
Wraps the entire operation in a database transaction.
Inserts all rows into the journal_entries table with the same transaction_id.
Calls the LedgerService to update the balances for each affected account.
Commits the database transaction.
6.2 LedgerService

Responsibility: To maintain the general_ledger table.
Key Method: postEntry(account_id, debit_amount, credit_amount).
Logic:
Calculates the change in balance based on the account's normal_balance type.
Issues a SELECT ... FOR UPDATE to lock the account's row in the general_ledger.
Updates the balance field with an atomic UPDATE general_ledger SET balance = balance + ? WHERE account_id = ?.
This prevents race conditions when multiple transactions affect the same account concurrently.
7.0 Example Transaction Flows
Scenario 1: Generating a $50 Invoice

Event: InvoiceGenerated (invoice_id: 123, amount: 50.00)
Listener: HandleInvoiceGenerated
Journal Entries Created by JournalingService:
Debit Accounts Receivable (Asset): $50.00 - Money we expect to receive increases.
Credit Service Revenue (Revenue): $50.00 - Revenue earned increases.
Scenario 2: Receiving a $50 Cash Payment for that Invoice

Event: PaymentReceived (invoice_id: 123, amount: 50.00, method: 'cash')
Listener: HandlePaymentReceived
Journal Entries:
Debit Cash (Asset): $50.00 - Our cash on hand increases.
Credit Accounts Receivable (Asset): $50.00 - Money we expect to receive decreases, as it has now been received.
Result: After both transactions, Accounts Receivable has a net zero change for this invoice, Cash is up by $50, and Service Revenue is up by $50. The books are balanced.

8.0 Financial Reporting (ReportingService)
The ReportingService will generate standard financial statements by querying the general_ledger and chart_of_accounts tables for specific date ranges.

Balance Sheet:
Presents a snapshot of Assets, Liabilities, and Equity accounts at a single point in time.
Validates that Assets = Liabilities + Equity.
Income Statement (Profit & Loss):
Summarizes Revenue and Expense accounts over a period of time (e.g., a month or quarter).
Calculates Net Income = Total Revenue - Total Expenses.
Accounts Receivable (A/R) Aging Report:
This is a crucial operational finance report. It queries the invoices table (not the ledger) to categorize all unpaid invoices by how long they have been overdue (e.g., 0-30 days, 31-60 days, 61-90 days, 90+ days).
This report is essential for the finance team to manage collections.
9.0 Risks
Risk	Description	Mitigation Strategy
Unbalanced Journal Entries	A bug allows an unbalanced transaction to be committed to the journal.	This would corrupt all financial data. The JournalingService's validation that debits === credits is a critical, mandatory check. This service must have 100% unit test coverage.
Incorrect Account Mapping	An event listener posts a transaction to the wrong account in the Chart of Accounts.	The mappings in the event listeners must be reviewed by a finance professional. The initial Chart of Accounts setup is a critical onboarding step. QA plans must include "T-account" analysis to verify transactions are posted correctly.
Performance of Ledger Updates	With high transaction volume, the row-level locking on the general_ledger table could become a bottleneck.	This is a known scalability challenge in accounting systems. For our initial scale, row-level locking is sufficient. Future optimizations could include batching ledger updates or using more advanced ledger data structures.
Data Immutability Violation	A developer writes code that updates a journal_entry record.	This is strictly forbidden. The journal_entries table should be treated as append-only. Database-level permissions could be used to revoke UPDATE privileges on this table for the main application user, allowing INSERT only.