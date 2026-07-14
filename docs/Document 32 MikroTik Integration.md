Document 32: MikroTik Integration
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document provides a detailed technical specification for the integration between the SkyFi Networks platform and MikroTik RouterOS devices. It defines the communication protocols, the specific API commands to be used, the design of the abstraction layer, and the security and error handling procedures.

The goal is to create a reliable, secure, and maintainable integration that serves as the definitive blueprint for all interactions with MikroTik hardware, enabling the automation of network provisioning and management.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the overall integration architecture, including the Adapter Pattern and communication strategy.
Backend Developers	Implement the MikroTikAdapter class, including the underlying API client library and all specific command implementations.
Network Engineers	Critical Stakeholders. Responsible for configuring the MikroTik devices to allow API access, setting up required profiles and queues, and validating that the commands sent by the system have the intended effect.
DevOps/Security	Ensure secure network connectivity between the application servers and the MikroTik routers.
3.0 Architectural Approach: The Adapter Pattern
As established in previous documents, our entire network management strategy hinges on the Adapter Pattern.

NetworkDeviceDriver Interface: A PHP interface that defines a generic set of capabilities (e.g., createPppoeSecret, getInterfaceStats). This contract is vendor-agnostic.
MikroTikAdapter Class: A concrete implementation of the NetworkDeviceDriver interface, specifically for MikroTik devices. All the "dirty" work of translating our application's generic command into a specific MikroTik RouterOS API call happens here.
Architectural Justification: This is the most critical design decision. It isolates all MikroTik-specific code into a single, swappable component. This:

Simplifies Business Logic: The PppoeService and HotspotService don't need to know they are talking to a MikroTik device; they just talk to the NetworkDeviceDriver.
Enables Future Expansion: Supporting a new vendor (e.g., Ubiquiti) means creating a new UbiquitiAdapter class, not rewriting the entire NMS module.
Improves Testability: The MikroTikAdapter can be easily mocked in unit tests for the services that use it.
Integration Diagram:

mermaid

graph TD
    A[Business Logic (e.g., PppoeService)] --> B{NetworkDeviceDriver Interface};
    
    subgraph "Vendor Implementations"
        C[MikroTikAdapter]
        D[Future: UbiquitiAdapter]
        E[Future: RadiusAdapter]
    end

    B -- "is implemented by" --> C;
    B -- "is implemented by" --> D;
    B -- "is implemented by" --> E;
    
    C --> F[MikroTik RouterOS API Client Library];
    F --> G((MikroTik Router));

    style B fill:#cde4ff,stroke-width:4px
4.0 Communication Protocol & Library
Protocol: MikroTik RouterOS API (Port 8728 for plain-text, 8729 for TLS).
Security: We must use the TLS-encrypted port (8729) for all production communication. This requires generating and installing a valid TLS certificate on each MikroTik router that the system will manage.
PHP Library: A well-maintained third-party PHP library for the MikroTik RouterOS API will be used (e.g., pear/routeros_api, evilfreelancer/routeros-api-php). The choice of library will be made based on its support for TLS, error handling, and overall code quality. The MikroTikAdapter will be the only part of our codebase that interacts directly with this library.
5.0 MikroTik Device Configuration Prerequisites
For a router to be managed by SkyFi Networks, a Network Engineer must perform the following setup:

Create an API User:
Create a dedicated user group with limited permissions (e.g., api, read, write). It should not have full admin privileges.
Create a user for the SkyFi system and assign it to this group.
Restrict this user's access to only be allowed from the specific IP address(es) of our application servers.
Enable the API Service:
Enable the api-ssl service on port 8729.
Disable the unencrypted api service on port 8728.
Install TLS Certificate:
Import a valid TLS certificate (ideally from a private CA or a public CA if the router is on a public IP) onto the router and assign it to the api-ssl service.
Define Service Profiles:
Create the necessary ppp profiles and ip hotspot profiles. The names of these profiles (e.g., plan-100-20) must match the router_profile_name field in our service_plans table. These profiles will contain the rate limits (queues) and address pools for each service tier.
Firewall Rules: Ensure firewall rules allow traffic from our application servers to port 8729 on the router.
6.0 Core Command Mappings
This table maps the key functions of our system to the specific MikroTik RouterOS API commands that the MikroTikAdapter will execute.

SkyFi Function	RouterOS Path	Key Parameters
Create PPPoE User	/ppp/secret/add	name, password, service=pppoe, profile, comment
Disable PPPoE User	/ppp/secret/set	.id (to identify the secret), disabled=yes
Enable PPPoE User	/ppp/secret/set	.id, disabled=no
Update PPPoE Profile	/ppp/secret/set	.id, profile
Remove PPPoE User	/ppp/secret/remove	.id
Get Active PPPoE Sessions	/ppp/active/print	(Query with ?service=pppoe)
Terminate PPPoE Session	/ppp/active/remove	.id (to identify the active session)
Create Hotspot User	/ip/hotspot/user/add	name, password, profile, limit-uptime, limit-bytes-total
Remove Hotspot User	/ip/hotspot/user/remove	.id
Get System Resources	/system/resource/print	(Returns uptime, CPU load, memory)
Get Interface Stats	/interface/monitor-traffic	interface, duration (for real-time bandwidth graphs)
Important Note on Identification: MikroTik commands often operate on an internal .id. The adapter's logic must first query to find the .id based on a known property (like name for a pppoe secret) before it can perform an update or remove action. This two-step process (find, then act) is a common pattern.

7.0 Error Handling and Resilience
Connection Errors: If the adapter cannot connect to the router (timeout, TLS error, auth failure), it must throw a specific MikroTikConnectionException.
Command Errors: If a command is sent but the router responds with an error (e.g., "invalid arguments," "item not found"), the adapter must throw a MikroTikCommandException containing the router's error message.
Retries: The services that call the adapter (e.g., PppoeService) are responsible for catching these exceptions and implementing a retry strategy. For transient network issues, a job should be re-queued with an exponential backoff delay.
State Consistency: If a command to the router fails, the state in our own database must not be updated. For example, if disabling a PPPoE secret fails, the customer's service.status must not be changed to suspended. The operation should be retried until it succeeds.
8.0 The "Sync" and "Audit" Philosophy
Our system should be the source of truth, but we cannot assume the router's state will never diverge (e.g., due to manual changes by an engineer).

Scheduled Sync Jobs: A background job will run periodically (e.g., nightly) to perform a sync operation.
Example: For a given router, it will fetch all PPPoE secrets from the router (/ppp/secret/print).
It will then compare this list against the active services that are supposed to be on that router in the SkyFi database.
It will generate a "discrepancy report" identifying:
Secrets on the router that don't exist in our DB (potential manual adds).
Services in our DB that are missing a secret on the router (provisioning failures).
Mismatches in profiles between the DB and the router.
Audit UI: This report will be displayed in an admin UI, allowing a Network Engineer to review the discrepancies and choose a "reconciliation" action (e.g., "Create missing secret," "Remove orphan secret," "Update profile").
9.0 Risks
Risk	Description	Mitigation Strategy
Performance Bottleneck	Making many sequential API calls to a single router is slow and can overload the router's CPU.	Use bulk commands where possible. The MikroTik API allows tagging commands. When possible, prepare a set of commands and send them in a single request. For monitoring, be judicious about polling frequency. Polling every router for all stats every 5 seconds is not scalable.
Credential Management	Storing and managing API credentials for hundreds of routers is a security risk.	Credentials must be encrypted at rest in the database. A centralized secret management system (like HashiCorp Vault) is the enterprise-grade solution for this, where the application retrieves credentials at runtime instead of storing them.
API Instability	The chosen third-party PHP library for the API is abandoned or has bugs.	Choose the library carefully based on its maintenance history and community support. The Adapter Pattern contains the risk, as we would only need to swap out the library usage within the adapter, or write our own client if necessary.
Manual Override Conflicts	A network engineer manually changes a setting on the router, which is then overwritten by the next automated job from the SkyFi system.	This is a process and communication issue. The SkyFi platform must be established as the single source of truth for configuration. Any manual changes should be considered temporary fixes, and the "correct" change should then be made in the SkyFi UI, which will then push the authoritative configuration to the router. The "Sync/Audit" feature helps identify these conflicts.