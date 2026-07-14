Document 37: Vendor Management
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the Vendor Management module. This module serves as a centralized repository for all information related to the suppliers of goods and services to SkyFi Networks.

The purpose is to create a single source of truth for vendor data, enabling:

Standardized and efficient procurement processes.
Tracking of vendor performance and history.
Centralized management of contacts, addresses, and payment terms.
A foundation for the Purchasing and future Accounts Payable modules.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the data models and service architecture for managing vendor information.
Backend Developers	Implement the API endpoints for CRUD operations on vendor entities.
Frontend Developers	Build the user interface for creating, viewing, and managing the vendor list and detail pages.
Purchasing / Inventory Manager	Primary Stakeholders. Responsible for creating and maintaining the accuracy of vendor records.
3.0 Core Concepts
Vendor: A business entity (e.g., MikroTik, Streakwave, Alliance Communications) from which SkyFi Networks procures hardware, software, or services.
Vendor Contact: An individual person associated with a vendor, such as a sales representative or an accounting clerk.
Vendor Address: A physical or mailing address associated with a vendor.
Payment Terms: The agreed-upon conditions for paying a vendor's invoices (e.g., "Net 30," "Due on Receipt").
Procurement History: A consolidated view of all purchase orders and items procured from a specific vendor over time.
4.0 Data Model Architecture
4.1 vendors Table

This is the central table for the module.

Column	Type	Description
id	INT	PK: Unique identifier for the vendor.
name	VARCHAR(255)	UK: The legal name of the vendor company.
status	ENUM(...)	active, inactive, on_hold.
website	VARCHAR(255)	The vendor's primary website URL.
default_payment_terms_id	INT	FK: (Future) Links to a payment_terms table.
notes	TEXT	General notes about the vendor relationship.
tax_id	VARCHAR(50)	The vendor's tax identification number.
4.2 vendor_contacts Table

A vendor can have multiple points of contact.

Column	Type	Description
id	BIGINT	PK
vendor_id	INT	FK: The vendor this contact works for.
first_name	VARCHAR(100)	Contact's first name.
last_name	VARCHAR(100)	Contact's last name.
email	VARCHAR(255)	Contact's email address.
phone	VARCHAR(50)	Contact's phone number.
role	VARCHAR(100)	The contact's role (e.g., "Sales Rep," "Accounts Receivable").
is_primary	BOOLEAN	Flag to indicate the main point of contact.
4.3 vendor_addresses Table

A vendor can have multiple addresses (e.g., for shipping vs. payment). This can be a dedicated table or leverage the generic addresses table with a polymorphic relationship. For simplicity and specificity, we'll define a dedicated table here.

Column	Type	Description
id	BIGINT	PK
vendor_id	INT	FK: The vendor this address belongs to.
type	ENUM(...)	ordering, payment, shipping_origin.
line1, city, state...	VARCHAR	Standard address fields.
ERD Snippet:

mermaid

erDiagram
    vendors {
        int id PK
        varchar name UK
        enum status
    }

    vendor_contacts {
        bigint id PK
        int vendor_id FK
        varchar first_name
        varchar email
    }

    vendor_addresses {
        bigint id PK
        int vendor_id FK
        enum type
        varchar line1
    }
    
    purchase_orders {
        bigint id PK
        int vendor_id FK
        varchar po_number
    }

    vendors ||--|{ vendor_contacts : "has"
    vendors ||--|{ vendor_addresses : "has"
    vendors ||--o{ purchase_orders : "receives"
5.0 Service Architecture and API
5.1 VendorService

Responsibility: Manages the business logic for vendors.
Key Methods:
createVendor(data): Creates a new vendor and its primary contact/address.
getVendorWithDetails(id): Retrieves a single vendor and eager-loads its related contacts, addresses, and a summary of its purchase history.
getVendorPurchaseHistory(id): A dedicated method to query the purchase_orders and purchase_order_items tables to provide a full history of what has been ordered from a vendor.
5.2 API Endpoints

The module will expose a standard set of RESTful CRUD endpoints:

GET /api/v1/vendors: Returns a paginated list of all vendors.
POST /api/v1/vendors: Creates a new vendor.
GET /api/v1/vendors/{id}: Retrieves full details for a single vendor, including contacts and addresses.
PUT /api/v1/vendors/{id}: Updates a vendor's details.
DELETE /api/v1/vendors/{id}: Sets a vendor's status to inactive. (Soft delete pattern).
GET /api/v1/vendors/{id}/purchase-orders: A specific endpoint to retrieve the PO history for a vendor.
6.0 User Interface
6.1 Vendor List View (/inventory/vendors or /purchasing/vendors)

Description: A standard data table view showing all vendors.
Columns: Vendor Name, Status, Primary Contact Phone, Primary Contact Email.
Functionality:
Searchable and sortable.
A "Create Vendor" button that opens a form/modal.
Each row is clickable, navigating to the Vendor Detail View.
6.2 Vendor Detail View (/vendors/{id})

Description: A "360-degree view" for a single vendor, consolidating all related information.
Layout: A multi-section or tabbed interface.
Header/Summary: Displays the vendor's name, status, and primary contact information.
Contacts Section: A list of all vendor_contacts, with options to add, edit, or remove contacts.
Addresses Section: A list of all vendor_addresses.
Purchase History Tab: A data table listing all purchase_orders issued to this vendor. This provides an immediate, powerful view of the business relationship's history and volume.
Items Tab: A list of all inventory_items associated with this vendor. This shows everything we buy from them.
Settings Tab: A form to edit the vendor's core details and notes.
7.0 Integration with Other Modules
Purchasing: The vendors table is a hard dependency for the Purchasing module. The "Vendor" dropdown on the "Create Purchase Order" form is populated directly from this module's data.
Inventory Management: The inventory_items table is linked to vendors. This allows us to answer questions like, "Which vendors can we buy 'MikroTik RouterBOARD hEX' from?" or "Show me everything we buy from Streakwave."
Finance (Future Accounts Payable): The vendor record will be essential for managing bills and payments. The default_payment_terms and payment address will be used to create Vendor Bills and process payments.
8.0 Workflow Example: Creating a New Purchase Order
A Purchasing Agent needs to order more equipment. They navigate to the "Create Purchase Order" page.
The first field is a "Vendor" dropdown, which is populated by an API call to GET /api/v1/vendors.
The agent selects "Streakwave" from the list.
The form automatically populates the default shipping address and payment terms based on the selected vendor's record.
The agent then adds line items. The "Add Item" search bar queries only the inventory_items associated with the selected vendor ("Streakwave").
This tight integration streamlines the PO creation process and reduces errors by ensuring that only valid items from the correct vendor can be ordered.
9.0 Risks
Risk	Description	Mitigation Strategy
Duplicate Vendor Records	A user creates a new record for "MikroTik, Inc." when a record for "MikroTik" already exists, splitting the purchase history.	The UI should perform a search for similar names before a new vendor is created to alert the user of a potential duplicate. The name field should have a unique constraint in the database. A "merge vendors" utility could be built for administrators to clean up duplicates.
Stale Contact Information	A sales rep at a vendor leaves, but their contact info remains in our system. POs are sent to a dead email address.	This is a data hygiene and business process issue. The system can help by flagging vendors with no activity for a long period (e.g., no POs in the last 12 months) for review. A quarterly data audit process should be encouraged.
Data Siloing	The vendor management system is not well-integrated, forcing users to copy-paste information into purchase orders.	The architecture explicitly integrates Vendor Management as a foundational dependency for the Purchasing module. The UI workflow (as described in section 8.0) ensures a seamless flow of data from the vendor record to the PO form.