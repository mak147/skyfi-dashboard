Document 34: Wireless Infrastructure
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for managing the Wireless Infrastructure within the SkyFi Networks platform. It focuses on modeling the logical and radio-frequency (RF) aspects of the network that are installed on physical towers. This includes sector antennas, backhaul links, and their associated coverage areas.

The purpose is to create a detailed model of the Radio Access Network (RAN) that enables:

Granular capacity planning and management.
More accurate and sophisticated service availability analysis.
Better visualization of network topology and dependencies.
Streamlined troubleshooting by linking customers to the specific antenna they are served by.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the data models for representing wireless assets and their RF characteristics.
Backend Developers	Implement the services and API endpoints for managing wireless infrastructure entities.
Frontend Developers	Build the UI for visualizing antenna coverage on a map and managing infrastructure details within the Tower view.
Network Engineers	Primary Stakeholders. Provide the RF data (azimuth, beamwidth, frequency) and validate the accuracy of the network model.
3.0 Core Concepts
Sector Antenna: A directional antenna on a tower that provides coverage to a specific area or "sector." It is the primary point of connection for multiple subscribers (Point-to-MultiPoint).
Backhaul Radio (PtP): A high-capacity, point-to-point radio link that connects a tower back to the core network or to another tower. This is the "backbone" of the wireless network.
Access Point (AP): The radio hardware that is physically connected to a sector antenna. In many integrated systems, the AP and antenna are a single unit.
Coverage Area (GIS Data): The geographical area, often represented as a polygon or a circular/sectoral shape on a map, where an antenna is expected to provide a usable signal.
Azimuth & Beamwidth: Key RF parameters for a sector antenna.
Azimuth: The direction the antenna is pointing, in degrees from North (0°).
Beamwidth: The width of the antenna's signal, in degrees.
Frequency: The radio frequency channel the equipment is operating on. Critical for interference management.
Conceptual Relationship:

A Tower hosts multiple Assets. Some of these assets are Sector Antennas. Each Sector Antenna has an Access Point radio. This Sector/AP combination has specific RF properties (azimuth, frequency) and serves multiple Customer CPEs (which are also Assets). The Tower is connected to the network via a Backhaul Radio link.

4.0 Data Model Architecture
This architecture extends the models from Tower and Inventory Management. We will add a dedicated table to store RF-specific attributes for wireless assets.

4.1 wireless_details Table

This table will have a one-to-one relationship with the assets table. It only contains records for assets that are wireless equipment (sector antennas, APs, backhaul radios, CPEs).

Column	Type	Description
asset_id	BIGINT	PK, FK: Links directly to the assets.id table.
frequency_mhz	INT	The operating frequency in MHz (e.g., 5810).
channel_width_mhz	INT	The width of the radio channel (e.g., 20, 40, 80).
azimuth_degrees	INT	For directional antennas, the pointing direction (0-359).
beamwidth_degrees	INT	For sector antennas, the horizontal beamwidth.
eirp_dbm	INT	Effective Isotropic Radiated Power (transmit power).
ssid	VARCHAR(32)	The SSID being broadcast, if applicable.
parent_ap_id	BIGINT	FK (Self-referencing): For a CPE, this links to the asset_id of the Sector AP it's connected to.
link_target_id	BIGINT	FK (Self-referencing): For a PtP backhaul, this links to the asset_id of the radio at the other end of the link.
ERD Snippet:

mermaid

erDiagram
    assets {
        bigint id PK
        varchar asset_tag
        varchar category
        int location_id
    }
    
    wireless_details {
        bigint asset_id PK, FK
        int frequency_mhz
        int azimuth_degrees
        bigint parent_ap_id
    }

    assets ||--o{ wireless_details : "has RF details"
Justification: Separating RF details into its own table keeps the primary assets table clean and generic. Not every asset (like a switch or UPS) has an azimuth or frequency. This one-to-one relationship is highly efficient and follows good normalization principles.

4.2 Updates to Existing Models

services table: A new cpe_asset_id (FK to assets.id) column will be added. This creates a direct link from a customer's active service to the specific piece of equipment installed at their premise.
assets table:
The category field in inventory_items becomes very important for driving UI and logic (e.g., if category is "Sector Antenna", show fields for azimuth and beamwidth).
The parent_ap_id relationship (modeled in wireless_details) is critical. It allows us to build the entire topology: Customer -> Service -> CPE Asset -> AP Asset -> Tower.
5.0 Service Architecture & API
5.1 WirelessInfrastructureService (Part of the Network Module)

Responsibility: Provides methods for querying and managing the logical wireless network.
Key Methods:
getTowerTopology(towerId): A complex query that builds a hierarchical object representing a tower's full infrastructure: its backhauls, its sectors, and for each sector, the list of connected customer CPEs. This is the data source for the Tower Detail View.
updateWirelessDetails(assetId, data): Updates the RF properties for a specific wireless asset.
getCoverageData(): A method that queries all active sector antennas and their wireless_details (azimuth, beamwidth, tower location) to generate a set of GIS data used by the frontend map and the ServiceAvailabilityService.
5.2 API Enhancements

GET /api/v1/towers/{id}/topology: A new endpoint that returns the detailed topology from getTowerTopology.
PUT /api/v1/assets/{id}/wireless-details: An endpoint to update the RF-specific data for a wireless asset.
GET /api/v1/network/coverage: An endpoint that returns a GeoJSON object representing the calculated coverage areas of all active sectors.
6.0 User Interface
6.1 Tower Detail View -> "Infrastructure" Tab

Description: A new or enhanced tab that provides a visual and logical representation of the tower's wireless setup.
Functionality:
Visual Diagram: A graphical representation (using Mermaid.js or a similar library) of the topology fetched from /towers/{id}/topology. It would show the backhaul link coming into the tower, branching out to the sector APs, and listing the number of clients on each sector.
Sectors List: A detailed list of all sector antennas at the site. Each item would show:
Asset Name/Tag.
Azimuth, Beamwidth, Frequency.
Number of connected subscribers.
Real-time capacity utilization (an advanced metric from monitoring).
Clicking an item would highlight it in the diagram and potentially show the list of connected customers.
6.2 Main Network Map View

Coverage Overlay: A new toggleable layer on the main network map.
Functionality:
When enabled, it fetches the GeoJSON data from /network/coverage.
It renders semi-transparent polygons on the map representing the theoretical coverage area of each sector antenna.
The color of the polygon could indicate the band (e.g., 5GHz, 2.4GHz) or capacity utilization.
This provides an invaluable visual tool for network planning and for sales agents performing serviceability checks.
Coverage Visualization Example:

mermaid

---
title: Sector Coverage on Map
---
pie
    "Sector 1 (Azimuth 0°)" : 60
    "Sector 2 (Azimuth 120°)" : 60
    "Sector 3 (Azimuth 240°)" : 60
    "Uncovered" : 180
(This Mermaid diagram is a conceptual representation. The actual UI would be a real map with cone-shaped polygons originating from tower markers.)

7.0 Integration with Service Availability
The ServiceAvailabilityService becomes much more powerful with this architecture.

Enhanced check() Logic:
Find towers within a certain radius of the prospect's coordinates.
For each nearby tower, get its active sector antennas.
For each sector, check if the prospect's coordinates fall within the antenna's calculated coverage polygon (a geospatial ST_Contains query).
Line of Sight (LoS) Check (Advanced): For a more accurate check, the service could make an API call to a Digital Elevation Model (DEM) service (like Google's or a self-hosted one) to determine if there is a clear line of sight between the tower's height and the prospect's location, accounting for terrain elevation.
The service can now return not just isServiceable, but which specific sector on which tower would serve the customer, along with its current capacity.
8.0 Risks
Risk	Description	Mitigation Strategy
Model Complexity	The relationships between assets, wireless details, and locations can become complex and hard to query.	Use clear, consistent foreign keys and polymorphic types. The getTowerTopology service method will be complex, but it encapsulates this complexity, providing a simple, clean data structure to the API consumer. The database schema must be well-documented.
Inaccurate RF Data	Network engineers enter incorrect azimuth or beamwidth data, making the coverage map visualizations misleading.	This is a data accuracy and process issue. The UI for entering this data should be intuitive, perhaps with a visual compass rose to select the azimuth. A process must be in place where any physical change to an antenna on a tower requires an immediate update in the SkyFi system.
Theoretical vs. Real Coverage	The calculated coverage polygons on the map do not match real-world signal propagation due to foliage, buildings, etc.	The coverage map must always be presented as a theoretical guide, not a guarantee of service. This should be made clear in the UI. The LoS check is a critical enhancement to improve accuracy. Real-world signal readings from technician site surveys should be fed back into the system to build a more accurate, predictive model over time.
Vendor-Specific Metrics	Different vendors (MikroTik, Ubiquiti) report RF metrics (like signal strength, noise floor) differently.	This is a classic NMS challenge. The NetworkDeviceDriver interface will define generic metric names (e.g., getSignalStrengthDbm()). It is the job of each specific adapter (MikroTikAdapter, UbiquitiAdapter) to query the vendor's specific OID/API endpoint and normalize the value to fit the generic contract.