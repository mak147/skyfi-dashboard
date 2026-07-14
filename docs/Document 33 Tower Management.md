Document 33: Tower Management
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the Tower Management module within the SkyFi Networks platform. This module is responsible for cataloging and managing the physical locations and key assets that constitute the wireless network's points of presence (PoPs).

The goal is to create a centralized inventory of all physical tower sites, providing a foundation for:

Service availability and coverage mapping.
Network infrastructure planning and capacity management.
Asset tracking and maintenance scheduling.
Logical grouping of network devices and customer services.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the data models and relationships for towers and their associated assets.
Backend Developers	Implement the API endpoints for CRUD operations on tower and site data.
Frontend Developers	Build the user interface for viewing towers on a map, managing tower details, and viewing associated equipment.
Network Engineers	Primary Stakeholders. Provide and maintain the accuracy of all tower data, including locations, heights, and installed equipment.
3.0 Core Concepts
Tower/Site: A physical location where network equipment is installed. This could be a traditional lattice tower, a monopole, a grain silo, a tall building, or any other structure used as a PoP.
Asset: Any piece of physical equipment associated with a tower. This includes routers, switches, sector antennas, backhaul radios, UPS systems, etc.
Geospatial Data: The precise GPS coordinates (latitude and longitude) of a tower are the most critical piece of data, driving serviceability checks and network visualization.
Hierarchy: The Tower is a top-level object in the physical network hierarchy. It "contains" network devices, which in turn "provide" service to customers.
Conceptual Hierarchy:

mermaid

graph TD
    Region --> Tower1[Tower A]
    Region --> Tower2[Tower B]

    Tower1 --> D1[MikroTik Router]
    Tower1 --> D2[Sector Antenna 1]
    Tower1 --> D3[Sector Antenna 2]
    Tower1 --> D4[Backhaul Radio]

    D2 -- "Serves" --> C1[Customer X]
    D2 -- "Serves" --> C2[Customer Y]
    D3 -- "Serves" --> C3[Customer Z]
4.0 Data Model Architecture
This section details the database tables required to support Tower Management.

4.1 towers Table

This is the central table for this module.

Column	Type	Description
id	INT	PK: Unique identifier for the tower/site.
name	VARCHAR(100)	UK: A human-readable name for the site (e.g., "North Ridge Tower").
site_id	VARCHAR(50)	Optional unique identifier or code for the site.
status	ENUM(...)	planning, active, maintenance, decommissioned.
latitude	DECIMAL(10,7)	Critical: The precise latitude of the site.
longitude	DECIMAL(10,7)	Critical: The precise longitude of the site.
height_meters	DECIMAL(6,2)	The height of the structure in meters.
address_line1, city, state...	VARCHAR	The physical street address of the site.
access_notes	TEXT	Instructions for accessing the site (gate codes, contacts).
region_id	INT	FK: (Future) Links the tower to a geographical/business region.
4.2 assets Table (Generalized Inventory)

Instead of many specific equipment tables, we will use a single, flexible assets table.

Column	Type	Description
id	BIGINT	PK: Unique identifier for a single piece of equipment.
serial_number	VARCHAR(100)	UK: The manufacturer's serial number.
asset_tag	VARCHAR(50)	UK: SkyFi's internal asset tracking tag.
inventory_item_id	INT	FK: Links to the inventory_items table (the "SKU" or model type).
status	ENUM(...)	in_storage, deployed, under_repair, retired.
location_id	BIGINT	Polymorphic FK: Can be a tower_id, warehouse_id, or customer_id.
location_type	VARCHAR	The model type for the polymorphic relation.
purchase_date	DATE	Date the asset was acquired.
warranty_expires_at	DATE	Warranty expiration date.
Justification for Polymorphic Relation: An asset's location changes throughout its lifecycle. It starts in a warehouse, gets deployed to a tower, might be installed at a customer's premises (CPE), or moved to a repair depot. A polymorphic relationship (location_id, location_type) is the most flexible way to model this.

4.3 inventory_items Table

This table defines the "types" of assets. It's the product catalog for our own equipment.

Column	Type	Description
id	INT	PK
sku	VARCHAR(50)	UK: Internal Stock Keeping Unit.
name	VARCHAR(255)	Product name (e.g., "MikroTik RB4011iGS+RM").
vendor_id	INT	FK: The manufacturer (e.g., MikroTik, Ubiquiti).
category	VARCHAR(100)	e.g., "Router", "Switch", "Sector Antenna", "CPE".
5.0 Service Architecture and API
5.1 TowerService (Part of the Network Module)

Responsibility: Business logic for managing tower sites.
Key Methods:
createTower(data): Creates a new tower record.
getTowerDetails(id): Retrieves a tower and aggregates related data, such as a list of all assets currently located at that tower.
getTowersForMapView(): An optimized method that returns a lightweight list of all active towers (id, name, status, lat, lon) for efficient rendering on a map.
5.2 API Endpoints

GET /api/v1/towers: Returns a paginated list of all towers.
POST /api/v1/towers: Creates a new tower.
GET /api/v1/towers/map-points: The lightweight endpoint for the map view.
GET /api/v1/towers/{id}: Retrieves full details for a single tower.
PUT /api/v1/towers/{id}: Updates a tower's details.
GET /api/v1/towers/{id}/assets: Returns a list of all assets currently assigned to that tower location.
6.0 User Interface
6.1 Tower Map View (/network/towers)

Description: The primary landing page for Tower Management. It will be a full-screen interactive map (using Mapbox or a similar library).
Functionality:
Fetches data from the /towers/map-points endpoint.
Displays a marker for each tower on the map.
The marker's color will indicate the tower's status (e.g., green for active, orange for maintenance).
Clicking a marker opens a small popup/infowindow with the tower's name and a "View Details" link.
The view will also include a searchable, sortable data table list of the towers, which interacts with the map (clicking a row on the table pans the map to that tower's marker).
6.2 Tower Detail View (/network/towers/{id})

Description: A comprehensive "360-degree view" for a single tower site.
Layout: A multi-tabbed or multi-section layout.
Overview/Details Pane: Shows the core information from the towers table, a small embedded map showing its location, access notes, and contact info.
Equipment Tab: A data table listing all assets assigned to this tower (location_id = {tower_id}). Each row shows the asset tag, model name, status, and purchase date. Rows are clickable to navigate to the full Asset Detail View.
Services Tab: Shows a list of all active customer services that are associated with this tower. This provides a clear view of which customers are dependent on this site.
Monitoring Tab: Displays real-time and historical monitoring data for key equipment at the tower (e.g., router CPU/memory from the MikroTik integration, backhaul link status).
Photos/Documents Tab: A section to upload and view site photos, diagrams, and lease documents.
7.0 Integration with Other Modules
CRM (ServiceAvailabilityService): The towers table, with its precise coordinates, is the primary data source for the service availability check. This service will query towers within a certain radius of a prospect's address.
Network Management (PppoeService, HotspotService): A service record is linked to a tower, which in turn is linked to a mikrotik_router. This creates the logical chain needed for provisioning: Customer -> Service -> Tower -> Router.
Inventory & Asset Management: The assets table is the core of inventory management. The Tower Management module provides the "deployed" location context for these assets.
8.0 Risks
Risk	Description	Mitigation Strategy
Inaccurate Geospatial Data	Incorrect GPS coordinates are entered for a tower, leading to major errors in service availability checks and network planning.	The UI for creating/editing a tower must have an interactive map where the user can drop a pin to get the coordinates, rather than relying solely on manual entry. Data entry should be validated to be within reasonable bounds.
Data Silos	Asset information is tracked in a separate system or spreadsheet, and the data in the SkyFi platform becomes stale.	The platform must be established as the single source of truth for asset locations. The workflow for deploying or moving equipment must include updating the asset's location in the SkyFi system as a mandatory step. Using a mobile app for technicians with barcode scanning can greatly improve this process.
Poor Map Performance	The main map view becomes slow and unusable when displaying thousands of tower sites.	Use map rendering techniques like clustering. When zoomed out, multiple nearby markers are grouped into a single cluster icon. As the user zooms in, the clusters break apart into individual markers. The API endpoint (/towers/map-points) must be highly optimized and return minimal data.
Complex Asset Hierarchy	Modeling the physical connections between assets on a tower (e.g., which antenna is connected to which port on which radio) can become extremely complex.	For v1.0, we will keep it simple: assets are assigned to a tower. A future "advanced infrastructure" feature could introduce a more complex data model for tracking port-to-port connections, but this is not required for the initial core functionality.