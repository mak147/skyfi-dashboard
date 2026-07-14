Document 35: Inventory Management
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the Inventory Management module. This module is responsible for tracking all physical assets and stockable items owned by SkyFi Networks. It covers the entire lifecycle of an item, including its definition, stock levels in various locations, and assignment to specific towers, customers, or technicians.

The goal is to create a centralized, real-time inventory system that:

Provides an accurate count of all hardware assets and their current location.
Prevents stockouts and over-purchasing.
Tracks the value of inventory for financial accounting.
Streamlines the logistics for installations and repairs.
Integrates seamlessly with Purchasing, Tower Management, and Customer Management.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the data models and workflows for tracking stock and serialized assets.
Backend Developers	Implement the InventoryService, related data models, and API endpoints.
Frontend Developers	Build the UI for managing inventory items, stock levels, and viewing asset history.
Inventory Manager	Primary Stakeholder. Responsible for the accuracy of inventory data, managing stock counts, and defining item catalogs.
Field Technicians	Interact with the system to check out and return equipment from their vehicle stock.
3.0 Core Concepts
A critical distinction must be made between Stockable Items and Serialized Assets.

Inventory Item (The "SKU"): This is the definition or type of a product. It's the entry in the product catalog.
Example: "MikroTik hAP ac lite" or "100ft CAT6 Ethernet Cable".
Stock: This represents a quantity of a non-serialized Inventory Item at a specific location.
Example: 50 units of "100ft CAT6 Ethernet Cable" in the "Main Warehouse".
Asset (The "Instance"): This represents a single, unique, serialized piece of equipment. Each asset is an instance of an Inventory Item.
Example: The specific "MikroTik hAP ac lite" with serial number A1B2C3D4E5F6.
Analogy: Inventory Item is the blueprint. Stock is how many copies of the blueprint you have printed. Asset is a specific, numbered copy of that blueprint. We track quantities of consumables (cables, connectors) as Stock, but we track individual routers, radios, and switches as Assets.

4.0 Data Model Architecture
This architecture builds on models previously introduced.

4.1 Key Data Models

inventory_items: The master product catalog.
id, sku, name, description, category ('Router', 'Antenna', 'Cable', 'Consumable'), vendor_id.
A crucial new field: is_serialized (BOOLEAN). This flag determines if we track individual units as Assets or just quantities as Stock.
assets: The table for serialized items.
id, asset_tag, serial_number, inventory_item_id (FK), status ('In Storage', 'Deployed', 'Under Repair'), location_id (Polymorphic), location_type.
warehouses: Defines physical storage locations.
id, name (e.g., "Main Warehouse", "Technician Van - John D").
stock: Tracks quantities of non-serialized items.
inventory_item_id (PK, FK)
warehouse_id (PK, FK)
quantity (INT)
stock_movements: An immutable log of all changes to stock levels. This is critical for auditing.
id, inventory_item_id (FK), from_warehouse_id (Nullable FK), to_warehouse_id (Nullable FK), quantity, type (ENUM: purchase, transfer, install_use, adjustment), source_id (Polymorphic FK, e.g., to a Purchase Order or Work Order).
ERD Snippet:

mermaid

erDiagram
    inventory_items {
        int id PK
        varchar sku UK
        varchar name
        bool is_serialized
    }

    assets {
        bigint id PK
        varchar serial_number UK
        int inventory_item_id FK
        int location_id
        varchar location_type
    }
    
    warehouses {
        int id PK
        varchar name UK
    }

    stock {
        int inventory_item_id PK, FK
        int warehouse_id PK, FK
        int quantity
    }
    
    stock_movements {
        bigint id PK
        int inventory_item_id FK
        int from_warehouse_id FK
        int to_warehouse_id FK
        int quantity
        varchar type
    }
    
    inventory_items ||--|{ assets : "is instance of"
    inventory_items ||--|{ stock : "has quantity of"
    warehouses ||--o{ stock : "is location for"
    inventory_items ||--|{ stock_movements : "is moved"
5.0 Service Architecture & Workflows
5.1 InventoryService

Responsibility: The central service for all inventory-related logic.
Key Methods:
receiveStock(purchaseOrderId, items: array): Called when a purchase order is fulfilled. It iterates through the received items.
If an item is_serialized, it creates new records in the assets table.
If it is not serialized, it updates the quantity in the stock table for the destination warehouse.
It creates records in stock_movements to log the entire transaction.
transferStock(itemId, fromWarehouse, toWarehouse, quantity): Moves non-serialized stock between two locations. It decrements quantity from one stock record, increments another, and logs the movement.
moveAsset(assetId, toLocation): Changes the location_id and location_type for a serialized asset. This is used when assigning a router to a tower or a CPE to a customer.
assignToWorkOrder(workOrderId, items: array): Allocates specific assets or quantities of stock to an installation job. This can decrement stock from a warehouse and move it to a technician's "van" warehouse.
5.2 Inventory Lifecycle Workflow

Procurement: A Purchase Order is created for 10 "MikroTik hAP ac lite" routers (an inventory_item where is_serialized = true).
Receiving: The shipment arrives. The InventoryService::receiveStock method is called. The system creates 10 new rows in the assets table, each linked to the same inventory_item_id, and assigns them a location of "Main Warehouse". A stock_movements record is created for each new asset.
Allocation: A Work Order is created for a new customer installation. The assignToWorkOrder method is called.
Asset A1B2C3D4E5F6 (a specific hAP ac lite) is allocated.
InventoryService::moveAsset is called to change the asset's location from "Main Warehouse" to "Technician Van - John D".
Deployment: The technician completes the installation.
In the Work Order completion UI, the technician confirms the use of asset A1B2C3D4E5F6.
An event is dispatched. A listener calls InventoryService::moveAsset to change the asset's location from "Technician Van - John D" to location_id: {customer_id}, location_type: 'Customer'.
The services.cpe_asset_id is updated to link the customer's active service to this specific asset.
Return/Swap: If the CPE is swapped during a repair, the old asset's location is changed to "Under Repair Depot," and a new asset is assigned to the customer.
6.0 User Interface
Item Catalog (/inventory/items): A data table view of all inventory_items, allowing the Inventory Manager to define the products the company uses.
Stock Levels (/inventory/stock): A view, pivot-table style, showing a matrix of inventory_items (rows) and warehouses (columns) with the quantity at each intersection. This gives an at-a-glance overview of all stock.
Asset Management (/inventory/assets): A searchable, filterable data table of all serialized assets. Filters would include status, location, model type, etc.
Asset Detail View (/inventory/assets/{id}):
Shows all details of a specific asset (serial number, purchase date, warranty info).
Crucially, it displays a full history of the asset, built from the stock_movements log and location changes. E.g., "Received -> Main Warehouse -> Tech Van -> Deployed at Customer X".
Technician's Mobile UI: A simplified interface for technicians to:
View the inventory currently assigned to their "van" warehouse.
Mark items as used during a work order completion.
Scan a barcode on an asset to quickly look it up.
7.0 Integration with Other Modules
Purchasing: The Purchasing module triggers the receiveStock workflow.
Work Orders (Operations): Work orders consume inventory. The system should prevent a work order from being scheduled if the required items are not in stock or not allocated to the technician.
Customer Management: The cpe_asset_id on the services table creates a direct link. From a Customer's 360° View, you can see the specific serial number of the equipment at their house.
Finance: The value of inventory is a significant item on the balance sheet. The InventoryService will dispatch events (StockValueIncreased, AssetDeployed) that the Finance module can listen to, creating journal entries to debit/credit Inventory (Asset) and Cost of Goods Sold (Expense) accounts.
8.0 Risks
Risk	Description	Mitigation Strategy
Data Inaccuracy	Physical reality does not match the system's data (e.g., system says 10 routers are in the warehouse, but there are only 8).	This is the biggest risk in any inventory system. Regular physical inventory counts (cycle counting) are a required business process. The system must have a "Stock Adjustment" feature that allows an authorized user to correct the system quantity, which creates an auditable stock_movements record.
Workflow Bypass	A technician grabs a router from the shelf without checking it out in the system, breaking the entire tracking chain.	Process and UX are key. The system must be fast and easy to use, especially for technicians. Barcode scanning on a mobile app is a huge enabler. If the process is too cumbersome, people will work around it.
Complex Reconciliation	It becomes difficult to track the financial value of deployed assets.	Implement a standard costing method (e.g., FIFO, Average Cost). When an asset is purchased, its cost is recorded. When it's "used" on an install, an event is fired that allows the Finance module to move that specific cost from the Inventory asset account to the COGS expense account.
Polymorphic Relationship Complexity	Queries involving the polymorphic location on the assets table can be complex and less performant than simple foreign keys.	This is a known trade-off for flexibility. The number of location types is limited and known, so queries can be written to handle them. The performance impact is negligible at our expected scale compared to the benefit of the flexible model.