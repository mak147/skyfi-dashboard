Document 31: Hotspot Management
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the Hotspot Management system within the SkyFi Networks platform. It covers the creation, management, and monitoring of hotspot users, plans, and vouchers, primarily targeting MikroTik's Hotspot system.

The purpose is to enable SkyFi Networks to offer time- or data-based internet access in public areas (e.g., cafes, parks, marinas), creating a new revenue stream. This system will automate user generation, handle billing through prepaid vouchers, and provide a branded captive portal experience.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the hotspot service architecture, data models, and the captive portal interaction flow.
Backend Developers	Extend the NetworkDeviceDriver interface, implement hotspot logic in the MikroTikAdapter, and create the HotspotService.
Frontend Developers	Build the UI for managing hotspot plans, generating vouchers, and a simple, customizable captive portal template.
Network Engineers	Configure the Hotspot servers, walled garden entries, and user profiles on the MikroTik routers.
3.0 Core Concepts
Hotspot Server: A service on a MikroTik router that intercepts traffic from users on a specific network segment and forces them to a login page (Captive Portal) before granting internet access.
Walled Garden: A list of IP addresses or domains that a user can access before logging into the hotspot. This is critical for allowing access to payment gateways.
Hotspot User: A username/password credential stored on the router, similar to a PPPoE secret, but with specific limits (e.g., uptime limit, data limit).
Hotspot Profile: A configuration on the router that defines the shared properties for a group of hotspot users, such as rate limits and session timeouts.
Voucher: A pre-generated, single-use code that can be entered on the captive portal to create and log in with a temporary hotspot user.
Captive Portal: A web page hosted by the router (or an external server) that is presented to users for authentication.
4.0 Hotspot Workflow & Use Cases
Use Case 1: Prepaid Voucher Generation & Use

mermaid

flowchart TD
    subgraph "SkyFi Admin Portal"
        A[Admin selects a Hotspot Plan] --> B[Generate a batch of Vouchers]
        B --> C[Vouchers stored in DB & can be printed]
    end

    subgraph "End User Experience"
        D[User connects to Wi-Fi] --> E{Redirected to Captive Portal}
        E --> F[User enters Voucher Code]
        F -- "POST to API" --> G[System validates Voucher Code]
        G -- "Valid" --> H[System generates a temporary Hotspot User via MikroTik API]
        H --> I[System returns username/password to Portal]
        I --> J[Portal automatically logs user in]
        J --> K[Internet Access Granted]
    end
    
    style B fill:#cde4ff
    style H fill:#90EE90
Use Case 2: Direct Purchase on Captive Portal

mermaid

flowchart TD
    A[User connects to Wi-Fi] --> B{Redirected to Captive Portal}
    B --> C[User selects a plan (e.g., '1 Day Pass')]
    C --> D[User enters Credit Card info]
    D -- "POST to Payment Gateway (Stripe)" --> E{Payment Processed}
    E -- "Success" --> F[Payment Gateway webhook notifies SkyFi API]
    F --> G[API generates temporary Hotspot User via MikroTik API]
    G --> H[API associates user with device MAC address for auto-login]
    H --> I[Internet Access Granted]
5.0 System Architecture & Service Design
We will extend the NMS architecture established for PPPoE.

5.1 NetworkDeviceDriver Interface Extension

The existing NetworkDeviceDriver contract will be extended with hotspot-specific methods.

PHP

// src/Network/Contracts/NetworkDeviceDriver.php
interface NetworkDeviceDriver {
    // ... existing PPPoE methods ...

    // --- New Hotspot Methods ---
    public function createHotspotUser(array $params): bool;
    public function removeHotspotUser(string $username): bool;
    public function getHotspotUsers(): array;
    public function getHotspotActiveUsers(): array;
}
The MikroTikAdapter will implement these new methods using the corresponding RouterOS API commands (/ip/hotspot/user/add, etc.).

5.2 New Data Models

hotspot_plans:
id, name (e.g., "1-Hour Pass", "1-Day Pass"), price, duration_minutes, data_limit_mb, router_profile_name (The name of the profile on the MikroTik router).
hotspot_vouchers:
id, code (VARCHAR, UK - The unique voucher code), hotspot_plan_id (FK), status (ENUM: new, used, expired), expires_at (TIMESTAMP), used_by_customer_id (Nullable FK).
5.3 New Core Services

HotspotService

Responsibility: To orchestrate all hotspot management tasks.
Key Methods:
generateVouchers(hotspotPlan, quantity): Creates a batch of hotspot_vouchers records with unique, random codes. Returns the generated vouchers.
redeemVoucher(voucherCode): This is a critical method called by the captive portal's API endpoint.
Finds the voucher in the DB. Validates its status is new and it's not expired.
Marks the voucher as used within a transaction.
Generates a unique, temporary username and password (e.g., hs-1a2b3c).
Determines the correct router based on the request context (e.g., which location the captive portal is for).
Calls the NetworkDeviceDriver->createHotspotUser() method with the generated credentials and limits from the associated hotspot_plan.
Returns the temporary username and password to be used for login.
provisionUserAfterPayment(hotspotPlan, macAddress): Similar to redeemVoucher but triggered by a payment gateway webhook. It can create a user tied to a MAC address for seamless login.
6.0 Captive Portal Architecture
A simple, customizable Captive Portal is a key part of the product.

Hosting: While MikroTik can host simple HTML pages, a modern JS-based portal is too complex. Therefore, the Captive Portal will be a separate, lightweight web application hosted on our infrastructure (e.g., as a static site on S3/Cloudflare Pages).
Walled Garden Configuration: The IP address/domain of our Captive Portal application and our Payment Gateway (Stripe) must be added to the "Walled Garden" list on every hotspot router. This allows unauthenticated users to load the login page and make a payment.
Workflow:
User connects to Wi-Fi.
MikroTik DNS intercepts their request and redirects them to our hosted Captive Portal URL, appending query parameters like the user's MAC address and original destination URL (.../login?mac=...&orig_dest=...).
The portal (a React/Vite app) loads. It displays options like "Login with Voucher" or "Buy a Pass."
Voucher Flow: The user enters a voucher code. The portal makes an API call to POST /api/v1/hotspot/redeem-voucher. Our backend validates the voucher and uses the MikroTik API to create a temporary user. It returns the temporary username and password to the portal.
The portal then automatically submits a hidden form POSTing directly to the MikroTik's own login URL (http://{router-ip}/login) with the temporary credentials.
The MikroTik router authenticates the user and redirects them to their original destination.
7.0 User Interface (Admin Portal)
Hotspot Plans: A CRUD interface under /network/hotspot-plans for an admin to define the available plans.
Voucher Management: A UI at /network/vouchers where an admin can:
Select a plan and generate a batch of vouchers.
View a data table of all generated vouchers with their status (new, used).
Export a list of voucher codes as a CSV for printing.
Active Users: A view at /network/hotspot-active-users that polls the getHotspotActiveUsers method on a selected router and displays a real-time list of connected hotspot users.
8.0 Security Considerations
Voucher Code Entropy: Voucher codes must be generated with sufficient randomness and length to be unguessable. Use a cryptographically secure random string generator.
Rate Limiting: The redeem-voucher API endpoint must be strictly rate-limited to prevent brute-force attacks trying to guess valid voucher codes.
Captive Portal Security: The communication between the captive portal and the SkyFi API must be over HTTPS.
Payment Security: By using Stripe Elements or a similar solution on the captive portal, sensitive credit card data is sent directly from the user's browser to Stripe, bypassing our servers entirely and keeping us out of the primary scope of PCI DSS compliance.
9.0 Risks
Risk	Description	Mitigation Strategy
Walled Garden Misconfiguration	If the payment gateway's IP addresses are not correctly added to the walled garden, users cannot buy a pass, and a major revenue stream is blocked.	Maintain a clear, documented list of required walled garden entries. Create a "health check" or "audit" script that an admin can run to verify the configuration of a hotspot router against the system's requirements.
Captive Portal Hijacking	A sophisticated attacker spoofs the captive portal to phish for credentials or payment information.	The captive portal must be served over HTTPS with a valid certificate. Users should be educated to look for the lock icon.
Orphaned Hotspot Users	A bug in the system creates temporary hotspot users on the router but fails to clean them up after they expire.	Implement a scheduled cleanup job (hotspot:cleanup). This job will periodically query the hotspot users on each router and compare them against the hotspot_vouchers or a temporary user log in our DB. It will remove any users that are expired or should no longer be active.
Single Point of Failure	If the central SkyFi API or the hosted captive portal application goes down, no one can log into any hotspot anywhere.	This is a significant risk of a centralized portal. The infrastructure for the portal and API must be highly available (as defined in Doc 06). For mission-critical deployments, a hybrid model where a lightweight version of the portal is cached on the router itself could be explored as a future enhancement.