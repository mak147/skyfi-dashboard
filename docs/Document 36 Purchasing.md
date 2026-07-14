Document 36: Purchasing
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the Purchasing module within the SkyFi Networks platform. This module manages the lifecycle of acquiring goods and services from external vendors, primarily focusing on the creation, approval, and fulfillment of Purchase Orders (POs).

The purpose is to create a structured and auditable procurement process that:

Standardizes how requests for new inventory and services are made.
Provides clear approval workflows to control spending.
Tracks orders from issuance to receipt.
Integrates seamlessly with the Inventory and Vendor Management modules.
Lays the groundwork for future integration with an Accounts Payable system.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the data models and state machine for the purchase order lifecycle.
Backend Developers	Implement the PurchaseOrderService, approval workflows, and API endpoints.
Frontend Developers	Build the UI for creating, viewing, and managing purchase orders.
Inventory Manager / Purchasing Agent	Primary Stakeholders. Use the system to create POs, track orders, and manage vendor relationships.
Finance Department	Use the system to track financial commitments and approve large expenditures.
3.0 Core Concepts
Vendor: An external company from which we purchase goods or services. (Managed in the Vendor Management module).
Purchase Order (PO): A formal, legally binding document issued by SkyFi Networks to a vendor, committing to pay for a specified list of items at an agreed-upon price.
PO Line Item: A single item on a PO, specifying the inventory_item, quantity, and unit price.
Approval Workflow: A configurable process that requires one or more managers to approve a PO before it can be sent to the vendor.
Receiving: The physical act of accepting a shipment from a vendor and verifying that it matches the PO. This action triggers the update in the Inventory module.
4.0 Purchasing Workflow
This workflow details the lifecycle of a single Purchase Order.

mermaid

flowchart TD
    A[Draft PO Created] --> B{Submit for Approval}
    B -- "Approval Required" --> C{Approval Workflow}
    B -- "No Approval Needed" --> E[Status: Approved]
    
    C --> D{Manager Approves/Rejects}
    D -- "Approved" --> E
    D -- "Rejected" --> F[Status: Rejected]
    F --> A
    
    E --> G[Send PO to Vendor]
    G --> H[Status: Sent]
    H --> I{Vendor ships goods}
    
    I --> J[Goods Arrive at Warehouse]
    J --> K[Receive Shipment via UI]
    K --> L[Status: Partially Received or Fully Received]
    
    subgraph "Integration Point"
        K -- "Triggers" --> M[InventoryService.receiveStock]
        M --> N[Inventory & Assets are updated]
    end
    
    L --> O[PO Closed]
    
    subgraph "Future AP Integration"
        O --> P[Vendor Bill is created in Accounts Payable]
    end

    style M fill:#cde4ff
5.0 Data Model Architecture
5.1 purchase_orders Table

Column	Type	Description
id	BIGINT	PK: Unique identifier.
po_number	VARCHAR(50)	UK: Human-readable, sequential PO number (e.g., "PO-2023-0001").
vendor_id	INT	FK: The vendor the PO is issued to.
status	ENUM(...)	draft, pending_approval, approved, rejected, sent, partially_received, fully_received, closed.
order_date	DATE	The date the PO is created.
expected_delivery_date	DATE	The date the vendor is expected to deliver.
shipping_address_id	INT	FK to an addresses table (our own warehouse address).
total_amount	DECIMAL(12,2)	The calculated total value of the PO.
notes	TEXT	Internal notes or notes for the vendor.
5.2 purchase_order_items Table

Column	Type	Description
id	BIGINT	PK
purchase_order_id	BIGINT	FK: The PO this line item belongs to.
inventory_item_id	INT	FK: The item being ordered.
description	VARCHAR(255)	Description of the item (can be overridden from the default).
quantity_ordered	INT	The number of units ordered.
quantity_received	INT	The number of units received so far.
unit_price	DECIMAL(10,2)	The price per unit, as agreed with the vendor.
5.3 po_approvals Table

This table logs the approval history for a PO.

Column	Type	Description
id	BIGINT	PK
purchase_order_id	BIGINT	FK: The PO being approved.
approver_id	INT	FK: The user who made the decision.
status	ENUM('approved', 'rejected')	The decision made.
comments	TEXT	Optional comments from the approver.
decision_at	TIMESTAMP	The time the decision was made.
6.0 Service Architecture
6.1 PurchaseOrderService

Responsibility: Manages the entire lifecycle and business logic of purchase orders.
Key Methods:
createPurchaseOrder(vendor, items, notes): Creates a PO in draft status.
submitForApproval(po): Changes the status to pending_approval and triggers the approval workflow (e.g., sends a notification to the designated approver).
approvePurchaseOrder(po, approver): Called by an authorized user. Logs the approval and updates the PO status to approved.
sendToVendor(po): Generates a PDF of the PO and uses the NotificationService to email it to the vendor's contact email. Updates status to sent.
receiveItems(po, receivedItems: array): This is the critical integration point.
It validates that the received items and quantities are valid for the PO.
It updates the quantity_received on each purchase_order_item.
It calls the InventoryService::receiveStock method, passing the details of the received items. This is what actually brings the items into our inventory.
It updates the PO's main status to partially_received or fully_received based on whether all ordered items have been accounted for.
6.2 Approval Workflow Logic

The approval logic will be configurable in the system settings.
Example Rule: "If po.total_amount > $5,000, require approval from a user with the Finance Manager role."
When a PO is submitted for approval, the system checks these rules and assigns the approval task to the appropriate user(s). The target user receives an in-app and/or email notification.
7.0 User Interface
PO Dashboard (/purchasing/orders): A data table view of all purchase orders, filterable by status, vendor, and date. This is the main workspace for a purchasing agent.
PO Creation/Edit Form: A comprehensive form for building a new PO.
Select a vendor.
Add line items by searching the inventory_items catalog.
Manually set quantities and unit prices.
The form automatically calculates line totals and the grand total.
PO Detail View (/purchasing/orders/{id}):
Displays all details of a single PO.
Shows a clear status timeline (Draft -> Approved -> Sent -> Received).
Includes the approval history section.
Crucially, includes a "Receive Shipment" button/modal for when the physical goods arrive. This modal will display the expected items and quantities and allow the user to input the quantities actually received.
8.0 Integration with Other Modules
Vendor Management: The Purchasing module is a primary consumer of data from the vendors table. You cannot create a PO without a vendor.
Inventory Management: This is the most critical integration. The fulfillment of a PO directly triggers the creation of assets or the increase of stock levels in the Inventory module. The stock_movements log will reference the PO as the source of the new inventory.
Finance (Accounts Payable - Future): Once a PO is fully received, it represents a financial liability. The purchase_order data will be the basis for creating a Vendor Bill in a future Accounts Payable system. The system can then match the vendor's invoice against the PO and the receiving records before issuing payment.
9.0 Risks
Risk	Description	Mitigation Strategy
Bypassing the Process	Staff purchase items outside of the PO system ("maverick spending"), leading to a loss of financial control and inaccurate inventory.	This is a business process and policy issue. The PO system must be declared the only approved method for procuring inventory. The system should be made easy to use to discourage workarounds.
Receiving Errors	An inventory manager incorrectly records the quantity of items received, causing a discrepancy between physical stock and system stock from day one.	The "Receive Shipment" UI must be very clear, showing expected vs. actual quantities. For high-value items, a two-person verification process could be implemented. Barcode scanning can dramatically reduce data entry errors.
Price Discrepancies	The price on the vendor's final invoice does not match the price on the PO.	The future Accounts Payable module will handle this with a "three-way match": it will compare the Vendor Invoice against both the Purchase Order and the Receiving Slip. Discrepancies will be flagged for manual review by the finance team before payment is approved.
Approval Bottlenecks	A manager is on vacation, and POs are stuck in pending_approval, halting procurement.	The approval workflow should support delegating approval authority or setting up multi-person approval groups, where any one person in the group can approve. Automated reminders should be sent for pending approvals.