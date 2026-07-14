Document 26: Billing Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture of the Billing module for the SkyFi Networks platform. It details the data models, service logic, and automated processes for managing service plans, generating invoices, processing payments, and handling the entire subscription lifecycle.

The goal is to design an automated, accurate, and auditable billing system that:

Maximizes revenue by ensuring every service is billed correctly and on time.
Minimizes revenue leakage through automated proration and dunning.
Provides a clear and transparent billing experience for customers.
Gives the finance department full control and visibility over the revenue stream.
Is flexible enough to handle future products and pricing models.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Define the billing architecture, ensuring data integrity, accuracy, and scalability.
Backend Developers	Implement the core billing services, jobs, and financial calculation logic.
Finance Department	Act as the primary stakeholders, defining and validating all business rules related to billing, proration, and collections.
QA Engineers	Create exhaustive test plans covering all billing scenarios, including edge cases for proration, upgrades, and cancellations.
3.0 Core Billing Principles
Immutability: Once an invoice is issued (status is not draft), it must not be changed. Any corrections must be handled by issuing a new credit note or a debit note, which creates a clear and auditable paper trail.
Atomicity: All financial operations (creating an invoice and its line items, applying a payment) must be performed within a database transaction to ensure atomicity. If any part of the operation fails, the entire transaction is rolled back, preventing partial or corrupt financial data.
Accuracy: All monetary calculations must be performed with exact precision. We will use dedicated decimal/money libraries, not floating-point numbers.
Automation: The entire recurring billing lifecycle—from invoice generation to payment collection and suspension for non-payment—must be fully automated.
Source of Truth: The billing system is the single source of truth for what a customer owes. Its data drives the dunning process and network service provisioning status.
4.0 High-Level Billing Workflow
mermaid

flowchart TD
    subgraph "Subscription Management"
        A[Customer signs up for Service Plan] --> B[Create `Service` record with `billing_cycle_anchor_date`]
        B --> C{Service Activated}
        C -- triggers --> D[Generate First Prorated Invoice]
    end

    subgraph "Recurring Billing (Automated Job)"
        E[Daily Billing Job Runs] --> F{Find Services where<br>next_bill_date = Today}
        F --> G[For each Service...]
        G --> H[1. Generate new `Invoice`]
        H --> I[2. Add `InvoiceItems` for Service Plan]
        I --> J[3. Apply any available `Credits`]
        J --> K[4. Calculate `next_bill_date`]
        K --> L[5. Dispatch `InvoiceGenerated` Event]
    end

    subgraph "Payment & Collections"
        M[Customer Pays via Portal/Auto-Pay] --> N[Payment Gateway processes payment]
        N -- Webhook/Callback --> O[PaymentService receives notification]
        O --> P[1. Create `Payment` record]
        P --> Q[2. Apply Payment to `Invoice` balance]
        Q --> R{Invoice balance == 0?}
        R -- Yes --> S[Update Invoice status to `Paid`]
        S --> T[Dispatch `InvoicePaid` Event]
        R -- No --> T
    end
    
    T --> U[Dunning Process (See Doc 09)]

    style D fill:#cde4ff
    style L fill:#cde4ff
    style T fill:#cde4ff
5.0 Key Data Models and Concepts
(Refers to entities in Document 11, with additional billing-specific context)

service_plans: The product catalog. Defines the price and billing interval.
services: The customer's subscription. This is the central entity for billing. It must contain:
billing_cycle_anchor_date: The date that anchors the customer's billing cycle (e.g., the 15th of the month).
next_bill_date: The date the next invoice should be generated. This is calculated and updated after each billing run.
invoices: A request for payment for a specific billing period.
invoice_items: The line items on an invoice. These are critical for clarity and tax purposes.
payments: A record of money received from a customer.
credits: A ledger of non-cash value on a customer's account that can be applied to future invoices. A credit is created when a user overpays or is issued a refund-to-credit.
6.0 Core Service Architecture (Billing Module)
The Billing module will contain several specialized services, each with a distinct responsibility:

6.1 SubscriptionService

Responsibility: Manages the lifecycle of a Service subscription.
Key Methods:
createSubscription(customer, plan, startDate): Creates a new Service record, calculates the anchor date, and triggers the initial prorated invoice.
changeSubscriptionPlan(service, newPlan, changeDate): The most complex method. Handles upgrades/downgrades. It must calculate the proration for the unused time on the old plan and the used time on the new plan, generating credits or a new prorated invoice.
cancelSubscription(service, cancelDate): Marks a service for cancellation and may trigger a final prorated invoice or credit for unused time.
6.2 InvoiceService

Responsibility: Manages the creation and state of invoices.
Key Methods:
generateRecurringInvoice(service): Called by the daily billing job. Creates the next invoice for a subscription.
generateProratedInvoice(service, periodStart, periodEnd): Creates a one-off invoice for a specific prorated period.
applyCreditToInvoice(invoice, credit): Applies available customer credit to an invoice's balance.
voidInvoice(invoice): Marks an issued invoice as void. This is a non-destructive action used to cancel an incorrect invoice.
6.3 ProrationService

Responsibility: Encapsulates all complex proration logic. This isolates the most difficult calculations into a single, highly testable service.
Key Methods:
calculateProration(plan, periodStart, periodEnd): Given a plan and a date range, calculates the exact amount owed. This must be precise, often calculating the cost per day and multiplying by the number of days in the period.
Business Rule: The proration calculation method must be configurable (e.g., based on a 30-day month vs. actual days in the month).
6.4 PaymentService

Responsibility: Handles incoming payments and reconciles them against invoices.
Key Methods:
processGatewayPayment(payload): Called by the payment gateway webhook controller. It validates the payload, finds the associated customer/invoice, and records the payment.
applyPaymentToInvoice(payment, invoice): Links a payment to an invoice, reduces the invoice balance, and updates the invoice status if fully paid.
7.0 Automated Processes
7.1 Daily Billing Job (billing:generate)

Trigger: Runs once daily via a cron scheduler (e.g., at 01:00 UTC).
Logic:
SELECT * FROM services WHERE next_bill_date = CURDATE() AND status = 'active'.
Iterate through the services in batches.
For each service, wrap the following in a database transaction:
a. Call InvoiceService->generateRecurringInvoice(service).
b. This service creates the invoice and invoice_items.
c. It calculates and updates the service.next_bill_date (e.g., adds 1 month).
d. It dispatches an InvoiceGenerated event to the notification queue.
Commit the transaction.
Log the results of the batch (e.g., "Generated 150 invoices successfully").
7.2 Dunning Job (billing:dunning)

As specified in Document 09. It runs daily to check for overdue invoices and trigger reminders and suspensions.
8.0 Proration Logic Example: Plan Upgrade
This is a critical and complex scenario that the architecture must handle flawlessly.

Scenario: A customer is on a $30/month "Basic" plan (i.e., $1/day). Their billing cycle is the 1st of the month. On January 15th, they upgrade to a $90/month "Pro" plan (i.e., $3/day).

Process handled by SubscriptionService->changeSubscriptionPlan():

The date of the change is January 15th. The current billing period is Jan 1st - Jan 31st.
Calculate Unused Time Credit:
The user paid $30 for the "Basic" plan for all of January.
They used 14 days (Jan 1-14).
They did not use 17 days (Jan 15-31).
Unused value = 17 days * $1/day = $17.00.
A Credit record for $17.00 is created and applied to the customer's account.
Calculate Prorated Charge for New Plan:
The user will be on the "Pro" plan for the remaining 17 days of January.
Prorated charge = 17 days * $3/day = $51.00.
Generate Immediate Prorated Invoice:
The InvoiceService is called to generate a new, immediate invoice for $51.00.
The system immediately attempts to apply the $17.00 credit from the customer's account to this new invoice.
The new invoice balance becomes $51.00 - $17.00 = $34.00.
The InvoiceGenerated event is dispatched for this $34.00 invoice, which is due immediately.
Update Subscription:
The service record is updated to point to the new "Pro" service_plan_id.
The next_bill_date remains February 1st. On Feb 1st, the customer will be billed the full $90 for the "Pro" plan.
9.0 Risks
Risk	Description	Mitigation Strategy
Calculation Errors	Bugs in proration or tax calculation logic lead to incorrect invoices.	This is the highest risk. All financial calculation logic must be encapsulated in its own service (ProrationService) and have 100% unit test coverage. The tests must include leap years, different month lengths, mid-day changes, and other edge cases.
Race Conditions	A payment comes in at the same time the dunning job is trying to suspend the customer.	Use pessimistic locking (SELECT ... FOR UPDATE) or database transactions with appropriate isolation levels when modifying a customer's or invoice's financial state. This ensures that only one process can modify a record at a time.
Billing Job Failure	The daily billing job fails halfway through, leaving some customers billed and others not.	The job must be idempotent and resumable. It should process customers in batches and log its progress. If it fails, it can be re-run, and it should be smart enough to skip customers that were already successfully processed in that day's run.
Floating Point Inaccuracies	Using float data types for money causes small rounding errors that compound over time.	Strictly forbidden. Use DECIMAL in the database and a dedicated Money/Decimal library in the PHP code for all financial calculations. All calculations should be done using the smallest unit (cents) as integers if a library is not available.