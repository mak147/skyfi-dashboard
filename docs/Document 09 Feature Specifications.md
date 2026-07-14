Document 09: Feature Specifications
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

Feature: Automated Service Suspension for Non-Payment
1.0 Purpose
This document provides a detailed specification for the "Automated Service Suspension for Non-Payment" feature, also known as the Dunning Process. It breaks down the high-level functional requirements (FR-BIL-009, FR-NMS-006) into a comprehensive guide for development, QA, and product ownership.

The purpose is to provide an unambiguous, end-to-end description of the feature's behavior, business rules, technical implementation details, and user impact, ensuring all teams have a shared understanding before a single line of code is written.

2.0 Responsibilities
Role	Responsibility
Product Owner	Own the business rules (e.g., grace periods, fees). Approve the final feature behavior and user-facing communications.
Development Lead	Oversee the technical implementation across the Billing and Network modules.
Backend Developer	Implement the scheduled job, service logic, and MikroTik integration.
Frontend Developer	Implement the UI indicators and notifications for suspended customers.
QA Engineer	Create and execute a test plan to verify all business rules, edge cases, and integrations.
3.0 Goals
Reduce Bad Debt: Automate the collections process to minimize revenue loss from delinquent accounts.
Improve Cash Flow: Incentivize timely payments by enforcing a clear consequence for non-payment.
Decrease Manual Workload: Eliminate the need for finance staff to manually track overdue accounts and suspend services.
Provide a Fair Customer Experience: Ensure the process is transparent, with clear notifications and grace periods before service interruption.
4.0 User Stories
This feature can be broken down into the following high-level user stories:

Story 1: As a Finance Manager, I want the system to automatically identify invoices that are overdue by a configurable number of days, so that I can initiate the dunning process.
Story 2: As a Finance Manager, I want the system to automatically send a series of reminder emails to customers with overdue invoices, so that they are aware of their outstanding balance before suspension.
Story 3: As a Finance Manager, I want the system to automatically apply a configurable late fee to an overdue invoice.
Story 4: As a Finance Manager, I want the system to automatically suspend the network service of a customer whose invoice remains unpaid after the final reminder, so that we can prevent further service usage without payment.
Story 5: As a Customer, I want to see a clear notification in my portal and receive an email when my service has been suspended, so that I understand why my internet is not working and how to fix it.
Story 6: As a Support Agent, I want to see a prominent status indicator on a customer's account showing they are suspended, so I can immediately inform them of the reason for their service issue.
5.0 Business Rules & Workflow
This workflow is triggered daily by a scheduled, automated process.

5.1 Configurable Settings (To be managed in Admin UI)

Setting	Type	Description	Default Value
dunning.grace_period_days	Integer	Days after an invoice's due date before it is considered "Overdue" and enters the dunning process.	3
dunning.reminder_1_days	Integer	Days after grace_period_days to send the first reminder email.	2 (i.e., 5 days past due)
dunning.reminder_2_days	Integer	Days after grace_period_days to send the second reminder email and apply a late fee.	7 (i.e., 10 days past due)
dunning.suspension_days	Integer	Days after grace_period_days to send the final notice and suspend the service.	12 (i.e., 15 days past due)
dunning.late_fee_amount	Currency	The flat fee to be applied to the invoice at the reminder_2 stage.	$10.00
dunning.reconnection_fee_amount	Currency	The fee automatically added for reactivating a suspended service.	$25.00
5.2 Workflow Diagram

mermaid

flowchart TD
    A[Daily Scheduled Job Starts] --> B{Find Invoices where<br>Status = 'Unpaid' AND<br>DueDate < (Today - grace_period_days)}
    B --> C{For each Overdue Invoice...}
    
    C --> D{Is customer already suspended?}
    D -- Yes --> X[End Process for this customer]
    D -- No --> E{Days Overdue >= suspension_days?}
    
    E -- Yes --> F[**Perform Suspension**]
    F --> G[1. Add Reconnection Fee to Account]
    G --> H[2. Call NetworkService to<br>Disable PPPoE User on MikroTik]
    H --> I[3. Update Customer Status to 'Suspended']
    I --> J[4. Send 'Service Suspended' Email]
    J --> X
    
    E -- No --> K{Days Overdue >= reminder_2_days?}
    K -- Yes --> L[**Send 2nd Reminder**]
    L --> M{Has Late Fee been applied?}
    M -- No --> N[1. Apply Late Fee to Invoice]
    M -- Yes --> O
    N --> O[2. Send '2nd Reminder' Email]
    O --> X

    K -- No --> P{Days Overdue >= reminder_1_days?}
    P -- Yes --> Q[**Send 1st Reminder**]
    Q --> R[Send '1st Reminder' Email]
    R --> X

    P -- No --> X
6.0 Technical Architecture & Implementation
This feature spans multiple modules, demonstrating the need for inter-module communication.

6.1 Backend Implementation

Scheduled Job (Task Scheduling):

A new command php artisan dunning:process will be created.
This command will be registered with the framework's kernel to run once every 24 hours (e.g., at 02:00 server time).
Billing Module (src/Billing/):

DunningService (src/Billing/Services/DunningService.php):
This new service will contain the core logic of the workflow diagram above.
It will be called by the dunning:process command.
It will use InvoiceRepository to find all eligible overdue invoices.
It will use CustomerRepository to update customer statuses.
Crucially, it will call the NetworkService and NotificationService via their injected interfaces.
Invoice Model (src/Billing/Models/Invoice.php):
Will be updated to have a late_fee_applied boolean flag.
Customer Model (src/Billing/Models/Customer.php):
Status enum will be updated to include suspended.
Network Module (src/Network/):

NetworkService (src/Network/Services/NetworkService.php):
Will expose a public method: disableCustomerService(int $customerId).
This method will:
Find the customer's active network service record.
Determine the correct MikroTik router the customer is on.
Use the MikroTikAdapter to connect to the router via API.
Find the customer's PPPoE user by their username (e.g., cust1234).
Execute the command to disable the PPPoE user.
Log the result (success or failure). If it fails, it should throw an exception that the DunningService can catch and log, so the account isn't marked as suspended if the network command failed.
Shared Module / Notifications:

NotificationService: Will be used to send templated emails for each stage of the process (Reminder 1, Reminder 2, Suspension Notice).
Email templates will be created with placeholders for customer name, invoice amount, due date, and a link to the payment portal.
6.2 Frontend Implementation

Customer 360 View (Staff Portal):

The main customer view will be updated to fetch the customer's status.
If customer.status === 'suspended', a prominent, non-dismissible banner/alert component will be displayed at the top of the customer's page.
Component: SuspendedCustomerBanner.tsx.
Content: "This customer's service is currently suspended due to non-payment."
Customer Portal (External):

When a suspended customer logs in, their main dashboard will display a similar banner.
Component: ServiceSuspendedNotification.tsx.
Content: "Your internet service has been suspended due to an overdue balance. To restore service, please pay your outstanding invoices. A reconnection fee of $25.00 will be applied."
The "Pay Now" button should be highly prominent.
7.0 API and Data Changes
GET /api/v1/customers/{id}: The response payload must be updated to include the status field (e.g., "status": "suspended").
customers table: The status column (likely an ENUM or VARCHAR) needs to be updated to include the 'suspended' value.
invoices table: A new late_fee_applied boolean column will be added, defaulting to false.
8.0 Validation & Edge Cases
The QA test plan must cover these scenarios:

Partial Payment: What happens if a customer pays part of their invoice but a balance remains? (The dunning process should continue based on the remaining balance).
Payment on Suspension Day: What happens if a customer pays their bill on the same day the suspension job runs? (The job must re-check the invoice balance immediately before issuing the suspend command). A database transaction should be used to ensure atomicity.
Multiple Overdue Invoices: How are late fees and suspensions handled if a customer has more than one overdue invoice? (The process should key off the oldest overdue invoice).
MikroTik API Failure: What happens if the system tries to suspend a service but the MikroTik router is unreachable? (The network command must fail, the exception must be logged, and the customer's status in the DB must not be changed to suspended. The action should be retried on the next run).
Manual Override: A Super Admin must have the ability to manually "forgive" a late fee or "unsuspend" a customer without payment, with the action being recorded in the audit log.
9.0 Security & Permissions
The configurable dunning settings must only be editable by users with a Finance Manager or Super Administrator role.
The ability to run the dunning process manually must be a distinct permission (dunning:execute).
The ability to manually unsuspend a customer must be a distinct permission (customer:unsuspend).
10.0 Risks
Risk	Description	Mitigation Strategy
Incorrect Suspension	A bug in the logic could lead to suspending a customer who has already paid.	Critical Risk. The test plan must be exhaustive. The logic must be: Get Invoice -> Check Balance AGAIN -> If balance > 0, THEN Suspend. This must be an atomic operation. Dry-run mode for the command is recommended for initial deployment.
Performance at Scale	The dunning job could become very slow as the customer base grows to tens of thousands.	The initial database query to find overdue invoices must be highly optimized with the correct indexes on the invoices table (status, due_date). Process customers in batches (e.g., 100 at a time) to manage memory usage.
Customer Backlash	If the process is not transparent, it can lead to angry customers and high support call volume.	The email templates must be crystal clear about dates, amounts, and consequences. The grace periods must be reasonable. The customer portal must provide clear information.
11.0 Future Expansion
SMS Notifications: Integrate with Twilio to send SMS alerts in addition to emails.
Auto-Pay Mitigation: For customers on auto-pay whose payment fails, trigger a specific notification flow ("Your payment method was declined...").
Tiered Dunning: Create different dunning profiles for different customer types (e.g., a more lenient process for high-value business clients).