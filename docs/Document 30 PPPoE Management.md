Document 30: PPPoE Management
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for managing Point-to-Point Protocol over Ethernet (PPPoE) services within the SkyFi Networks platform. It details the interaction between the core system, the network device abstraction layer, and the MikroTik routers.

The purpose is to design a robust and automated system for provisioning, managing, and monitoring PPPoE customer sessions, which is the primary method for authenticating subscribers and controlling their access in many WISP networks. This directly supports the vision of "Intelligent Provisioning."

2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the PPPoE service architecture, the adapter pattern for network hardware, and the data flow.
Backend Developers	Implement the PppoeService, the MikroTikAdapter, and the integration with the Billing and Customer modules.
Network Engineers	Act as subject matter experts on MikroTik RouterOS configuration. Configure PPPoE servers on the routers and provide API access details. Ensure network-level configurations (e.g., address pools, profiles) are correct.
QA Engineers	Develop integration tests that interact with a test MikroTik router to verify that provisioning, suspension, and termination actions work correctly.
3.0 Core Concepts
PPPoE Server: A service running on a network router (e.g., a MikroTik Core Router) that authenticates clients.
PPPoE Secret: A username/password pair stored on the PPPoE server. This is what the customer's CPE (Customer Premises Equipment) uses to log in.
PPPoE Profile: A configuration template on the router that defines service parameters like bandwidth limits (queues), session timeouts, and assigned IP address pools.
Active Session: A live, authenticated connection from a customer's CPE to the PPPoE server.
RADIUS (Remote Authentication Dial-In User Service): An alternative, more centralized authentication mechanism. While our v1.0 architecture will focus on direct router API interaction for simplicity, it must be designed to accommodate RADIUS in the future.
4.0 PPPoE Management Workflow
The system will automate the entire lifecycle of a PPPoE secret based on events in the customer and billing modules.

mermaid

flowchart TD
    subgraph "Customer Lifecycle Events"
        A[Service Activated]
        B[Service Suspended]
        C[Service Reactivated]
        D[Service Plan Changed (Speed)]
        E[Service Disconnected (Canceled)]
    end

    subgraph "Network Module (PppoeService)"
        S1[Create/Enable PPPoE Secret]
        S2[Disable PPPoE Secret]
        S3[Enable PPPoE Secret]
        S4[Update PPPoE Secret Profile]
        S5[Remove PPPoE Secret]
    end

    subgraph "MikroTik Router API"
        M1["/ppp/secret/add or /ppp/secret/enable"]
        M2["/ppp/secret/disable"]
        M3["/ppp/secret/enable"]
        M4["/ppp/secret/set"]
        M5["/ppp/secret/remove"]
        M6["/ppp/active/print (for monitoring)"]
    end

    A --> S1 --> M1
    B --> S2 --> M2
    C --> S3 --> M3
    D --> S4 --> M4
    E --> S5 --> M5
    
    subgraph "Monitoring"
        PppoeService -- "Periodically calls" --> M6
        M6 --> PppoeService[Updates Active Session Info]
    end
5.0 System Architecture & Service Design
The key to a scalable NMS is abstraction. We must avoid tying our core business logic directly to MikroTik's specific API.

5.1 The Adapter Pattern

We will define a generic NetworkDeviceDriver interface that dictates the contract for what our application can do with a network device.

PHP

// src/Network/Contracts/NetworkDeviceDriver.php
interface NetworkDeviceDriver {
    public function connect(string $ip, string $username, string $password): bool;

    public function createPppoeSecret(string $username, string $password, string $profile, string $comment): bool;
    public function updatePppoeSecret(string $username, array $params): bool;
    public function disablePppoeSecret(string $username): bool;
    public function enablePppoeSecret(string $username): bool;
    public function removePppoeSecret(string $username): bool;

    public function getActivePppoeSessions(): array;
    public function terminatePppoeSession(string $username): bool;

    // ... other methods for hotspots, queues, etc.
}
The MikroTikAdapter will be a concrete implementation of this interface.

PHP

// src/Network/Adapters/MikroTikAdapter.php
class MikroTikAdapter implements NetworkDeviceDriver {
    private $routerosClient;

    // ... implements all methods from the interface
    public function createPppoeSecret(string $username, string $password, string $profile, string $comment): bool {
        // Contains the specific RouterOS API client code to execute:
        // $this->routerosClient->query('/ppp/secret/add', [
        //   'name' => $username,
        //   'password' => $password,
        //   'profile' => $profile,
        //   'comment' => $comment,
        //   'service' => 'pppoe'
        // ]);
        // ... return true/false based on response
    }
    // ...
}
Justification: This is the most important architectural decision. When we want to support Ubiquiti EdgeRouters, we simply create a UbiquitiAdapter that implements the same NetworkDeviceDriver interface. The core PppoeService logic does not change.

5.2 PppoeService

Responsibility: To orchestrate all PPPoE management tasks, acting as the bridge between the business domain and the network layer.
Key Methods:
provisionService(Service $service): This is the main entry point called by an event listener (e.g., HandleServiceActivated).
Gets the service object.
Determines the correct router to provision on (from service->tower->router).
Gets the router's credentials from the mikrotik_routers table.
Instantiates the MikroTikAdapter and connects to the router.
Calls the adapter's createPppoeSecret method, passing the username, password, and profile name derived from the service and service_plan objects.
Logs the result of the operation.
suspendService(Service $service): Similar logic, but calls disablePppoeSecret.
terminateService(Service $service): Calls removePppoeSecret.
syncActiveSessions(Router $router): A monitoring method, called periodically by a scheduled job. It calls getActivePppoeSessions and stores the session data (IP address, uptime) in a cache or a dedicated active_sessions table for display in the UI.
5.3 Data Model Integration

services table:
pppoe_username: The username for the PPPoE secret. This must be unique system-wide. A good convention is cust{customer_id} or a similar predictable format.
pppoe_password: A randomly generated, strong password.
service_plans table:
router_profile_name: A new column to store the exact name of the corresponding PPPoE Profile on the MikroTik router (e.g., plan-50-10). This decouples our internal plan name from the network configuration name.
mikrotik_routers table:
Stores the IP address and encrypted API credentials for each router.
6.0 User Interface Integration
Customer 360° View -> "Network" Tab:

Display Data:
PPPoE Username.
Static IP Address (if assigned).
From the syncActiveSessions data:
Current Session Status (Online/Offline).
Session IP Address.
Session Uptime.
Real-time bandwidth usage graphs (this is an advanced feature requiring more complex router polling).
Actions (for Network Engineers):
A "Kick" button that calls terminatePppoeSession to force a customer to disconnect and reconnect.
A "Sync Status" button to manually trigger a status refresh.
7.0 Security Considerations
API Credentials: Router API credentials stored in the database must be encrypted at rest.
Network Access: The application servers must be on a network segment that can reach the management interfaces of the MikroTik routers. This will likely involve firewall rules and potentially a VPN or dedicated management network. The connection should be over a secure channel if the RouterOS version supports it.
Error Handling: Failures to connect to a router or execute a command are Operational Errors. The PppoeService must catch exceptions from the adapter (e.g., ConnectionException, CommandFailedException), log them with WARNING or ERROR level, and potentially queue a retry. A failed provisioning attempt should not halt other system processes. The failed state should be made visible in the UI.
8.0 Future Expansion: RADIUS Integration
The current architecture, while simple and effective, requires the application to talk to many different routers. A more scalable, centralized approach is to use a RADIUS server.

How the architecture supports this:

The PPPoE servers on the MikroTik routers would be configured to authenticate against a central RADIUS server (e.g., FreeRADIUS).
The application's responsibility would shift from talking to MikroTik routers to managing users in the RADIUS database.
We would create a new RadiusAdapter that implements the NetworkDeviceDriver interface.
This RadiusAdapter's createPppoeSecret method would insert a record into the RADIUS radcheck table. disablePppoeSecret would delete or flag this record.
To switch the system to RADIUS, we would simply change the service provider binding in our DI container from MikroTikAdapter to RadiusAdapter. The PppoeService and all the business logic would remain unchanged. This demonstrates the power of the adapter pattern.
9.0 Risks
Risk	Description	Mitigation Strategy
Router API Changes	MikroTik updates RouterOS, and the API schema or command syntax changes, breaking our integration.	The MikroTikAdapter isolates this risk. Only one class would need to be updated. The third-party library used for the API communication should be kept up-to-date. Integration tests against a real router are essential to catch these breakages before deployment.
Network Unreliability	The connection between the application server and a router is down, preventing provisioning.	This is an expected operational issue. The PppoeService must have robust retry logic for all commands. Failed provisioning tasks must be placed in a "failed jobs" queue and be made visible on an admin dashboard for manual intervention.
Configuration Mismatch	A service_plan in the system refers to a router_profile_name that doesn't exist on the target router.	The provisioning command will fail. The error message from the router should be logged. A "sync" or "audit" tool should be developed for network engineers to run, which compares the profiles defined in the system against the actual profiles on a router to find mismatches.
Performance at Scale	Polling hundreds of routers for active sessions becomes slow and resource-intensive.	Switch to a RADIUS-based accounting system, where routers send accounting packets (start, stop, interim-update) to a central server. The application can then read from this central accounting log instead of polling each device. This is the standard industry solution for scaling.
