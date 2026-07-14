Document 39: Installation Workflow
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture and workflow for the customer installation process. It covers the creation and management of work orders, scheduling, technician assignment, and the final service activation steps.

The goal is to design a streamlined, semi-automated workflow that:

Ensures a smooth handoff from the sales team to the field operations team.
Provides technicians with all the information they need to perform an installation successfully on the first visit.
Gives managers visibility into the schedule and progress of all installation jobs.
Automates the final service activation and billing initiation upon successful installation.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the data models, state machine, and service interactions for the entire workflow.
Backend Developers	Implement the WorkOrderService, SchedulerService, and the integration points with CRM, Inventory, and NMS.
Frontend Developers	Build the UI for the scheduler (calendar/map view), the work order detail page, and the technician's mobile-first completion form.
Operations Manager / Dispatcher	Primary Stakeholders. Define the stages of a work order and the business rules for scheduling and assignment.
Installation Technicians	The primary users of the mobile-facing parts of this module. Their feedback on usability is critical.
3.0 Core Concepts
Work Order: The central object representing a single job to be performed at a customer's location. For this document, we focus on "Installation" work orders, but the same system can be used for "Repair" or "Upgrade" work orders.
Scheduler: A tool (often a calendar or map-based view) used by a dispatcher or manager to assign work orders to technicians based on availability, location, and skill set.
Site Survey: A preliminary assessment, sometimes part of the installation work order, to confirm signal quality and plan the equipment mounting and cable routing.
Checklist: A predefined list of tasks that a technician must complete and check off to ensure a quality installation.
Activation: The final step where a completed installation triggers the automated provisioning of the network service and the start of the billing cycle.
4.0 Installation Workflow & State Machine
This workflow begins immediately after the CRM's "Conversion" process.

mermaid

flowchart TD
    A[CRM: Quote Accepted & Converted] --> B(Work Order Created<br>Status: 'Pending')

    subgraph "Dispatch & Scheduling"
        B --> C{Dispatcher views Unscheduled Work Orders}
        C --> D[Assign to Technician & Schedule Date/Time]
        D --> E(Status: 'Scheduled')
        E --> F[Notification sent to Customer & Technician]
    end
    
    subgraph "Field Technician's Job"
        F --> G{Technician starts job}
        G --> H(Status: 'In Progress')
        H --> I[Travel to Site]
        I --> J[Perform Site Survey & Signal Test]
        J --> K{Signal is Viable?}
        K -- No --> L(Status: 'Failed - No Signal')
        L --> Z[End]

        K -- Yes --> M[Install CPE and run cable]
        M --> N[Complete Installation Checklist]
        N --> O[Capture photos, record asset serial numbers]
    end

    subgraph "Activation & Completion"
        O --> P{Technician submits Completion Form}
        P --> Q(Status: 'Completed')
        Q -- Event: `WorkOrderCompleted` --> R{System Automation}
        R --> R1[1. NMS: Provision PPPoE service]
        R1 --> R2[2. Billing: Activate subscription & start billing cycle]
        R2 --> R3[3. Inventory: Mark CPE asset as deployed to customer]
        R3 --> S(Customer Status: 'Active')
    end
    
    S --> Z
    
    style R fill:#cde4ff
5.0 Data Model Architecture
5.1 work_orders Table

Column	Type	Description
id	BIGINT	PK: Unique identifier.
wo_number	VARCHAR(50)	UK: Human-readable work order number (e.g., "WO-2023-0001").
customer_id	BIGINT	FK: The customer for whom the job is being done.
type	ENUM(...)	installation, repair, upgrade, site_survey.
status	ENUM(...)	pending, scheduled, in_progress, on_hold, completed, failed, canceled.
technician_id	INT	FK (Nullable): The user (technician) assigned to the job.
scheduled_at	DATETIME	The date and time of the appointment.
completed_at	DATETIME	The timestamp when the job was marked complete.
notes	TEXT	Internal notes for the dispatcher or technician.
source_ticket_id	BIGINT	FK (Nullable): If this is a repair, links back to the source support ticket.
5.2 work_order_items Table

This table lists the equipment required for the job.

Column	Type	Description
id	BIGINT	PK
work_order_id	BIGINT	FK
inventory_item_id	INT	FK: The type of item required (e.g., "MikroTik hAP ac lite").
quantity	INT	The quantity needed.
5.3 work_order_checklist_items Table

Column	Type	Description
id	BIGINT	PK
work_order_id	BIGINT	FK
task_description	VARCHAR(255)	The task to be done (e.g., "Verify signal strength > -65 dBm").
is_completed	BOOLEAN	Flag checked by the technician.
completed_by_id	INT	FK: The user who completed the task.
notes	TEXT	Optional notes or values recorded for the task.
6.0 Service Architecture
6.1 WorkOrderService

Responsibility: Manages the lifecycle and business logic of work orders.
Key Methods:
createInstallationOrder(customer, service): Called by the ConversionService. Creates the work_order and populates work_order_items based on the equipment bundled with the service_plan.
createRepairOrder(ticket): Creates a repair work order from a support ticket.
scheduleWorkOrder(workOrder, technician, scheduleDate): Assigns and schedules the job. Dispatches notifications.
completeWorkOrder(workOrder, completionData): The final, critical method.
Validates the completionData (e.g., checklist complete, asset serial numbers provided).
Updates the work_order.status to completed.
Dispatches the WorkOrderCompleted event. This is the key integration point that triggers the rest of the system.
6.2 SchedulerService

Responsibility: Provides data for the scheduling UI.
Key Methods:
getTechnicianAvailability(dateRange): Returns the schedules for all technicians, showing their existing appointments and free time slots.
getUnscheduledWorkOrders(): Returns a list of all work orders in pending status, ideally with their geographical location for map-based scheduling.
7.0 User Interface
7.1 Dispatcher/Manager UI

Scheduler View (/scheduler):

Description: A powerful, interactive scheduling interface.
Layout: A calendar view (day/week/month) with columns for each technician. Work orders are shown as blocks on the calendar. Alternatively, a map view showing technician locations and nearby unscheduled jobs.
Functionality: Allows a dispatcher to drag an unscheduled work order from a list and drop it onto a technician's timeline to schedule it.
Work Order Detail View (/work-orders/{id}):

A comprehensive view showing all customer info, required equipment, scheduled time, notes, and the status of the checklist.
7.2 Technician Mobile-First UI

My Jobs View: A simple list or map view of the technician's assigned jobs for the current day.
Job Detail View: A streamlined view of the work order, optimized for a mobile device.
Prominent customer name, address (with a "Navigate" button that opens Google/Apple Maps), and contact number.
A list of required equipment.
The interactive checklist.
A section for capturing photos (using the device's camera).
A section for scanning the barcode of the CPE and other assets being installed.
A final "Complete Job" button.
8.0 Integration Points
The Installation Workflow is a central hub of integration.

CRM: The workflow is initiated by the ConversionService after a sale is made.
Inventory: The workflow "consumes" inventory. The WorkOrderService must check stock availability before scheduling. The completion step updates the final location of the deployed assets.
NMS: The WorkOrderCompleted event triggers the PppoeService to provision the customer's network access.
Billing: The WorkOrderCompleted event triggers the BillingService to change the customer's service status to active and initiate the billing cycle.
Notifications: The system sends notifications to the customer (appointment confirmation/reminders) and the technician (new job assignment).
9.0 Risks
Risk	Description	Mitigation Strategy
Scheduling Conflicts	Two jobs are accidentally scheduled for the same technician at the same time.	The SchedulerService must have logic to prevent overlapping appointments. The UI should visually indicate when a technician is already booked.
Inventory Mismatch	A technician arrives on-site but doesn't have the correct equipment because the inventory system was wrong.	The link between Work Orders and Inventory must be tight. The system could prevent scheduling a job if the required items are not available in the assigned technician's van stock. This forces the logistic step of transferring stock to the van first.
Activation Failure	The technician completes the job, but the automated activation or billing process fails, leaving the customer without service or not being billed.	The WorkOrderCompleted event must be handled by a robust, queue-based listener. Any failures in the downstream processes (NMS, Billing) must be logged critically and placed in a "failed jobs" queue for manual review by an administrator.
Poor Mobile UX	The technician's UI is slow, hard to use in bright sunlight, or requires a constant internet connection in an area with poor cell service.	The mobile UI must be designed with these constraints in mind (high contrast mode, large touch targets). A "store and forward" or offline-first capability should be considered for a future version, where the technician can complete the form offline and it syncs automatically when a connection is re-established.