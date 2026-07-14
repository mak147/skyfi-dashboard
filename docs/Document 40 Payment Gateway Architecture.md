Document 40: Payment Gateway Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for integrating a third-party payment gateway into the SkyFi Networks platform. It details the interaction patterns, security measures, data models, and service design required to process customer payments securely and reliably.

The primary goal is to create a secure, abstract, and robust payment processing system that:

Maximizes security and minimizes PCI DSS compliance scope by never handling raw credit card data on our servers.
Provides a seamless payment experience for customers on the portal.
Enables automated recurring billing (Auto-Pay).
Is easily adaptable to support different payment gateways in the future.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the payment gateway abstraction layer, the data flow, and the security model.
Backend Developers	Implement the PaymentGatewayAdapter, PaymentService, and the webhook handler.
Frontend Developers	Integrate the payment gateway's client-side SDK (e.g., Stripe.js) to create secure payment forms.
Finance Department	Reconcile reports from the payment gateway with the records in the SkyFi system.
Security Team	Audit the implementation to ensure it meets PCI DSS compliance requirements.
3.0 Chosen Gateway & Architectural Strategy
Primary Gateway: Stripe.
Architectural Strategy: Adapter Pattern with Client-Side Tokenization.
3.1 Justification for Stripe:

Developer-First: Stripe offers excellent documentation, powerful APIs, and robust SDKs (Stripe.js, Stripe PHP).
Security & Compliance: Stripe handles the vast majority of PCI DSS compliance heavy lifting via its client-side libraries and tokenization model.
Feature Set: It provides a comprehensive suite of products including recurring subscriptions, modern payment elements, invoicing, and extensive fraud protection.
Global Reach: Supports multiple currencies and payment methods.
3.2 Justification for Adapter Pattern with Tokenization:
This is the most important architectural decision for security and long-term flexibility.

Tokenization Flow:
The user enters their credit card details into a form on our frontend.
This form is an iframe controlled by the payment gateway's JavaScript library (e.g., Stripe Elements).
The credit card data is sent directly from the user's browser to the payment gateway's servers. It never touches our application servers.
The payment gateway returns a single-use, non-sensitive token (e.g., tok_... or pm_...) to our frontend.
Our frontend sends this token to our backend.
Our backend uses this token to perform actions via the gateway's server-side API (e.g., create a charge, save the payment method).
Benefits:
PCI Compliance Scope Reduction: Since we never store, process, or transmit raw cardholder data, our PCI DSS compliance burden is drastically reduced to the simplest level (SAQ A).
Abstraction: Our core PaymentService will interact with a generic PaymentGateway interface, not directly with the Stripe SDK. This allows us to switch to another provider (e.g., Braintree, Adyen) in the future by simply creating a new adapter class, with zero changes to our core billing logic.
4.0 High-Level Architecture Diagram
mermaid

graph TD
    subgraph "Customer's Browser"
        A[Customer Portal UI]
        B[Payment Form (Stripe.js Elements)]
        A -- Renders --> B
    end

    subgraph "SkyFi Backend"
        D[API Controller]
        E[PaymentService]
        F{PaymentGateway Interface}
        G[StripeAdapter]
    end
    
    subgraph "Stripe Infrastructure"
        H[Stripe API]
        I[Stripe Vault]
    end

    B -- "1. Card details" --> H
    H -- "2. PaymentMethod Token (pm_...)" --> B
    A -- "3. POST /pay-invoice with Token" --> D
    
    D --> E
    E --> F
    F -- "is implemented by" --> G
    G -- "4. Use Token to create Charge via API" --> H
    
    H -- "5. (Async) Webhook Notification (payment.succeeded)" --> D[Webhook Controller]
    
    style B fill:#cde4ff,stroke-width:2px
    style G fill:#b4e8c8
5.0 Data Models and Storage
We will store references to Stripe objects, but never sensitive data.

customers table:
stripe_customer_id (VARCHAR, UK): The ID of the corresponding Customer object in Stripe (cus_...). This is essential for linking payments and saved payment methods.
payment_methods table:
id, customer_id (FK), stripe_payment_method_id (VARCHAR, UK, e.g., pm_...), brand ('Visa', 'Mastercard'), last4 ('4242'), exp_month, exp_year, is_default (BOOLEAN).
payments table:
transaction_id (VARCHAR, UK): The ID of the corresponding Charge or PaymentIntent object in Stripe (e.g., ch_..., pi_...). This is the unbreakable link for reconciliation.
Security: The mapping between our internal customer ID and the stripe_customer_id is sensitive information and should be protected accordingly.

6.0 Core Services and Workflows
6.1 PaymentGateway Interface and StripeAdapter

PHP

// src/Billing/Contracts/PaymentGateway.php
interface PaymentGateway {
    public function createCustomer(Customer $customer): string;
    public function createCharge(string $stripeCustomerId, int $amount, string $currency, string $paymentMethodId): ChargeResult;
    public function savePaymentMethod(string $stripeCustomerId, string $paymentMethodToken): PaymentMethodResult;
    public function createSubscription(string $stripeCustomerId, string $stripePriceId): SubscriptionResult;
}

// src/Billing/Adapters/StripeAdapter.php
class StripeAdapter implements PaymentGateway {
    // ... implements interface methods using the Stripe PHP SDK
}
6.2 PaymentService

Responsibility: To orchestrate payment-related business logic, acting as the client for the PaymentGateway interface.
Key Methods:
processOneTimePayment(customer, invoice, paymentMethodToken):
Checks if the customer has a stripe_customer_id. If not, calls paymentGateway->createCustomer().
Calls paymentGateway->createCharge() using the token.
On success, creates a payments record in our DB with the transaction_id from the gateway's response.
Dispatches a PaymentReceived event.
addPaymentMethod(customer, paymentMethodToken): Saves a new payment method for future use. Calls paymentGateway->savePaymentMethod() and creates a record in our payment_methods table.
processAutoPayForInvoice(invoice):
Finds the customer's default payment_method.
Calls paymentGateway->createCharge() using the saved stripe_payment_method_id.
Handles success or failure (e.g., dispatches PaymentFailed event).
6.3 Webhook Handling

This is critical for reliability, as some payment events are asynchronous.

Endpoint: A dedicated, public API endpoint: POST /api/v1/webhooks/stripe.
Security: The webhook handler must verify the signature of every incoming request using our Stripe webhook secret key. This prevents spoofing and ensures the request is genuinely from Stripe.
Logic (StripeWebhookController):
A switch statement on the event.type.
case 'payment_intent.succeeded': This is the confirmation that money has moved. The handler should call a service method to reconcile the payment, mark the invoice as paid, and provision service if necessary. This is a more reliable trigger than the initial API response.
case 'customer.subscription.created': If using Stripe's subscription engine, this confirms a recurring plan is set up.
case 'invoice.payment_failed': Triggers our internal PaymentFailed event, which can notify the customer and initiate dunning.
Idempotency: The webhook handler must be idempotent. It should check if it has already processed a given event ID to handle cases where Stripe retries sending a webhook.
7.0 User Experience (Frontend)
7.1 One-Time Payment Form (Customer Portal)

The page loads and makes an API call to the backend to get a "payment intent" secret from Stripe.
The frontend initializes Stripe.js with this secret.
The PaymentElement from Stripe is mounted, creating the secure iframe for card details.
On submit, the frontend calls stripe.confirmPayment(). This handles 3D Secure (SCA) if required.
The user is redirected to a success or failure page based on the outcome. The backend is updated via the webhook.
7.2 "Auto-Pay" / Recurring Billing

A customer saves a payment method on their profile page.
They can enable an "Auto-Pay" toggle on their account.
When the daily billing job generates a new invoice for this customer, it sees the auto-pay flag.
Instead of just sending a notification, it queues a ProcessAutoPayJob.
The job executes PaymentService->processAutoPayForInvoice(invoice), which charges the customer's default saved payment method via the Stripe API.
8.0 Risks
Risk	Description	Mitigation Strategy
PCI Compliance Scope Creep	A developer accidentally logs a payment method token or allows raw card data to hit our server.	This is the highest risk. Strict adherence to the tokenization model is mandatory. Code reviews must scrutinize all payment-related code. A PCI compliance checklist should be part of the PR process for this module. Automated secret scanning in the CI/CD pipeline should be implemented.
Webhook Failures	Our webhook endpoint is down, or Stripe fails to deliver a webhook, leading to a state mismatch (e.g., payment received by Stripe but invoice not marked as paid in our system).	Build a reconciliation process. A scheduled job should run daily to fetch a list of all successful payments from the Stripe API for the last 24 hours and compare it against our payments table. Any discrepancies should be flagged for manual review. The webhook handler must also be robust and return a 200 status code to Stripe quickly, while offloading the actual processing to a queue.
Vendor Lock-in	The entire system becomes deeply intertwined with Stripe's specific objects and flows (e.g., PaymentIntents, SetupIntents).	The Adapter Pattern is the primary mitigation. While the adapter itself (StripeAdapter) will be tightly coupled to Stripe, our core business logic (PaymentService, BillingService) is completely decoupled. Migrating to a new provider would involve writing a new adapter and a data migration script for customer/payment method tokens, which is a manageable project.
Handling Payment Failures	A customer's card is declined for auto-pay.	The invoice.payment_failed webhook is the trigger. The handler must kick off our standard dunning process. The customer must be notified immediately that their payment method was declined and needs to be updated.