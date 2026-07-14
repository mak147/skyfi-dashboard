Document 25: Notification Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the notification system within the SkyFi Networks platform. It covers the various channels of communication (email, SMS, in-app), the types of notifications, the triggering mechanisms, and the underlying technical implementation.

The goal is to create a robust, scalable, and extensible notification system that can:

Reliably deliver critical communications to customers and staff.
Decouple the act of "triggering" a notification from the mechanics of "sending" it.
Allow for easy addition of new notification types and delivery channels.
Provide users with control over their notification preferences.
Maintain a history of all communications for auditing and support purposes.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Define the notification architecture, including the service design, database schema, and queueing strategy.
Backend Developers	Implement the core NotificationService, job queue workers, and integrations with third-party providers.
Frontend Developers	Implement the in-app notification center and user preference settings UI.
Product Owner	Define the content, timing, and triggers for each specific notification.
3.0 Notification Types and Channels
3.1 Notification Types

Type	Description	Primary Audience
Transactional	Essential notifications triggered by a specific user or system action. These are non-negotiable and cannot be disabled.	Customers & Staff
Marketing	Promotional or informational broadcasts. Users must be able to opt-out.	Customers
System Alerts	Internal notifications about system health or critical events.	Staff (Ops/Devs)
In-App Activity	Notifications about actions happening within the application itself.	Staff
3.2 Delivery Channels

Channel	Primary Use Case	Third-Party Service
Email	Invoices, password resets, payment confirmations, dunning notices.	SendGrid (or similar like Mailgun, AWS SES)
SMS	Service outage alerts, appointment reminders, two-factor authentication.	Twilio (or similar)
In-App	"A new ticket was assigned to you," "Customer X's payment failed."	Custom (WebSockets, Polling)
Internal Alerting	"Database CPU is at 90%."	PagerDuty, Opsgenie, or Slack Webhooks
4.0 High-Level Architecture: Decoupled and Asynchronous
The core principle of our notification architecture is to decouple business logic from notification delivery. A service method (e.g., BillingService) should never directly call the SendGrid or Twilio API. Instead, it will dispatch a generic notification object to a central service, which then handles the processing and sending asynchronously.

Architectural Justification:

Resilience: If the email or SMS gateway is down, the business transaction (e.g., creating an invoice) doesn't fail. The notification is queued and will be retried later. This makes the core application more robust.
Performance: Sending emails or SMS messages can be slow. Offloading this work to a background queue worker prevents the user's initial HTTP request from being blocked, leading to a faster-perceived application.
Extensibility: To add a new channel (e.g., Push Notifications), we only need to add a new "driver" to the NotificationService. No business logic in other modules needs to change.
Architecture Diagram:

mermaid

graph TD
    subgraph "Business Logic Modules"
        A[BillingService]
        B[SupportService]
        C[AuthService]
    end

    subgraph "Core Notification System"
        D[NotificationService]
        E{Message Queue (e.g., Redis/SQS)}
        F[Notification Job Worker]
    end

    subgraph "Third-Party Gateways"
        G[Email Gateway (SendGrid)]
        H[SMS Gateway (Twilio)]
        I[In-App WebSocket Server]
    end
    
    subgraph Database
        DB[(notifications table)]
    end

    A -- "1. Dispatch(InvoiceGeneratedNotification)" --> D
    B -- "1. Dispatch(TicketAssignedNotification)" --> D
    C -- "1. Dispatch(PasswordResetNotification)" --> D

    D -- "2. Create record in DB & Enqueue Job" --> E
    D -- "2. " --> DB
    
    F -- "3. Dequeue Job" --> E
    F -- "4. Fetch Notification details" --> DB
    F -- "5. Send via appropriate driver" --> G
    F -- "5. " --> H
    F -- "5. " --> I

    F -- "6. Update status in DB (sent/failed)" --> DB
5.0 Detailed Component Design
5.1 Notification Class & Object

A standard Notification class will be created. Any event that needs to trigger a notification will create an instance of a class that extends this base class (e.g., InvoiceGeneratedNotification).
Properties:
recipient: The User or Customer object.
data: A payload of data to be used in the notification template (e.g., { invoice_id: 'INV-001', amount: '$50.00' }).
channels: An array of channels to send on (e.g., ['email', 'sms']).
5.2 NotificationService

This is the central entry point for all notifications.
It has one primary method: dispatch(Notification $notification).
dispatch Logic:
Determines the recipient(s) and desired channels from the notification object.
For each recipient/channel pair, it creates a record in the notifications database table with a pending status.
It dispatches a job to the message queue (e.g., SendNotificationJob) with the ID of the newly created notification record.
5.3 notifications Database Table

This table provides a crucial audit trail of all communications.

Column	Type	Description
id	UUID	PK: A unique ID for this specific notification delivery.
recipient_id	BIGINT	The ID of the User or Customer.
recipient_type	VARCHAR	The model type (App\Models\User).
notification_type	VARCHAR	The class name of the notification (e.g., InvoiceGeneratedNotification).
channel	ENUM(...)	The delivery channel (email, sms, in-app).
status	ENUM(...)	pending, sent, failed, read.
data	JSON	The payload data for the template.
sent_at	TIMESTAMP	Timestamp when the notification was successfully sent.
read_at	TIMESTAMP	Timestamp when the user read the in-app notification.
fail_reason	TEXT	If sending failed, the error message from the gateway.
5.4 SendNotificationJob (Queue Worker)

This is a background process that continuously pulls jobs from the queue.
Logic:
Receive the notification_id from the job payload.
Fetch the full notification record from the database.
Based on the channel field, select the appropriate "driver" (e.g., EmailDriver, SmsDriver).
The driver builds the final message from a template (e.g., a Blade/Twig view for email) using the data payload.
The driver makes the API call to the third-party gateway (SendGrid/Twilio).
On success, update the notification record's status to sent.
On failure, update the status to failed, record the fail_reason, and implement a retry strategy (e.g., retry up to 3 times with exponential backoff).
5.5 In-App Notifications

Technology: WebSockets (e.g., using Laravel Reverb, Pusher, or a self-hosted Soketi server).
Flow:
When the SendNotificationJob processes an in-app channel notification, its "driver" doesn't call an external API.
Instead, it broadcasts an event over a private WebSocket channel specific to the recipient (e.g., user.{user_id}).
The frontend, upon login, authenticates and subscribes to its user-specific channel.
When it receives an event, it displays a real-time toast notification and adds the message to the user's notification center list.
Fallback: If WebSockets are not immediately available, a simpler polling mechanism can be used as a fallback, where the frontend queries a GET /api/v1/notifications?unread=true endpoint every 30 seconds.
6.0 User Preferences
A notification_preferences table will be created: user_id, notification_type, channel, is_enabled (boolean).
The NotificationService will consult this table before creating a notification record. If a user has disabled a specific notification type/channel combination, the record is not created.
Important: Transactional notifications (e.g., PasswordResetNotification) will be hardcoded to ignore user preferences.
The frontend will provide a settings page for users to manage these preferences.
7.0 Templates
All notification content (email bodies, SMS text) must be stored in templates, not hardcoded in the application logic.
Email Templates: Will be created as view files (e.g., using Blade/Twig). They will have a consistent header/footer with SkyFi branding.
SMS Templates: Will be stored in language files for easy editing and translation. They must be kept concise.
8.0 Risks
Risk	Description	Mitigation Strategy
Gateway Failure	The primary email or SMS provider has a major outage.	The queue-based system provides resilience. The jobs will fail and be retried. The architecture must also include an Adapter Pattern for the drivers. This allows us to quickly switch to a backup provider (e.g., from SendGrid to AWS SES) by simply changing a configuration value.
Email Deliverability Issues	Our emails are being marked as spam.	Proper SPF, DKIM, and DMARC records must be configured for our domain. We must provide clear unsubscribe links for marketing emails and monitor our sender reputation.
Queue Worker Failure	The background queue processing stops, causing a backlog of notifications.	The queue workers must be monitored by a process manager like supervisor. The queue length should be a key metric on our operations dashboard, with alerts configured if it grows beyond a certain threshold.
Content Errors	A bug in a template causes broken or incorrect emails to be sent.	All email templates must be reviewable and testable. A "mail preview" tool should be used during development to allow developers to see how an email will render with different data without actually sending it.