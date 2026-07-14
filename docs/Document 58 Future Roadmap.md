Document 58: Future Roadmap
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Strategic Vision

1.0 Purpose
This document outlines the strategic future roadmap for the SkyFi Networks platform. It presents a high-level vision for features, architectural enhancements, and new business capabilities that could be developed after the core system (Phases 1-3) is successfully implemented and adopted.

The purpose of this roadmap is to:

Provide a long-term vision to guide ongoing architectural decisions.
Align technology evolution with potential future business goals.
Serve as a starting point for future product planning and investment discussions.
Ensure that the initial architecture is flexible enough to accommodate these future directions.
This is a strategic document, not a committed project plan. Items listed here are subject to change based on business priorities, market conditions, and technological advancements.

2.0 Guiding Themes
The future evolution of the platform will be guided by four primary themes:

Platform Expansion: Evolving from a WISP-centric tool to a multi-service ISP platform.
Intelligence & Automation: Leveraging data and AI to move from reactive management to predictive and automated operations.
Enhanced User Experience: Deepening the engagement and self-service capabilities for both customers and internal teams.
Architectural Evolution: Scaling and refining the technical architecture to support increased complexity and load.
3.0 Roadmap by Theme
The roadmap is organized by strategic theme, with potential initiatives classified by a rough time horizon.

Near-Term (1-2 years post-launch): Initiatives that are logical next steps and build directly on the core platform.
Mid-Term (2-4 years): Initiatives that involve significant new development or integration with new business verticals.
Long-Term (4+ years): Highly strategic or complex initiatives that represent a major evolution of the platform.
3.1 Theme: Platform Expansion
Goal: Grow beyond fixed wireless and support a wider range of ISP services and hardware.

Horizon	Initiative	Description	Business Value	Architectural Impact
Near-Term	Multi-Vendor NMS Support	Develop new Adapters for other major WISP hardware vendors like Ubiquiti (UISP/UniFi) and Cambium Networks.	Expands marketability to other WISPs; avoids vendor lock-in for SkyFi's own network growth.	Low. The NetworkDeviceDriver interface was designed specifically for this. Requires new adapter classes.
Mid-Term	VoIP Service Integration	Add modules for managing VoIP services, including DID (phone number) inventory, SIP provisioning, call detail record (CDR) processing, and rate-deck-based billing.	Opens a significant new revenue stream from residential and business phone services.	Medium. Requires new Voip module, new data models (dids, cdrs), and integration with a softswitch/VoIP platform API.
Mid-Term	Fiber-to-the-Home (FTTH) Support	Extend the provisioning and service models to support GPON/XGSPON fiber networks. This involves managing ONTs (Optical Network Terminals) instead of wireless CPEs.	Enables expansion into high-density urban markets where fiber is prevalent.	Medium. The NetworkDeviceDriver can be adapted for ONT management systems (OLT APIs). Billing concepts remain similar.
Long-Term	Platform Multi-Tenancy	Refactor the architecture to support a multi-tenant model, allowing SkyFi Networks to sell the platform as a SaaS product to other small ISPs.	Transforms the internal tool into a major external revenue-generating product.	High. This is a major architectural undertaking, requiring data segregation at every layer (DB, cache, file storage).
3.2 Theme: Intelligence & Automation
Goal: Use data analytics and machine learning to optimize operations and make smarter business decisions.

Horizon	Initiative	Description	Business Value	Architectural Impact
Near-Term	Advanced Network Automation	Build tools for automated, fleet-wide configuration backups, version-controlled config diffing/auditing, and orchestrated firmware upgrades for network devices.	Dramatically reduces network admin overhead and risk of human error during maintenance.	Medium. Extends the NMS module. Requires robust job queuing and state management.
Mid-Term	Predictive Analytics for Churn	Develop a machine learning model that analyzes customer data (payment history, ticket frequency, network quality metrics) to generate a "churn risk score" for each customer.	Allows the retention team to proactively engage at-risk customers, reducing churn and preserving revenue.	Medium. Requires a data pipeline to a machine learning platform (e.g., SageMaker). The score is then fed back into the CRM UI.
Mid-Term	Dynamic Bandwidth Management	Implement a system that can dynamically adjust customer bandwidth shaping based on real-time network congestion or time-of-day policies, offering "burst" speeds.	Improves overall network quality of experience. Enables new, more flexible service plan offerings.	High. Requires more advanced, real-time interaction with router queueing systems and a more complex NMS rules engine.
Long-Term	AI-Powered Predictive Maintenance	Analyze historical network metric trends to predict imminent hardware failures (e.g., a radio's signal is slowly degrading). Automatically generate a "preventive maintenance" work order.	Reduces customer-facing outages, lowers emergency repair costs, and improves network reliability.	High. Requires a mature data warehouse and significant investment in time-series analysis and ML modeling.
3.3 Theme: Enhanced User Experience
Goal: Provide best-in-class tools for both customers and internal staff.

Horizon	Initiative	Description	Business Value	Architectural Impact
Near-Term	Native Mobile App for Technicians	Develop a native iOS/Android app for field technicians, built on the existing REST API. It would offer better performance, offline capabilities, and deeper device integration (camera, GPS, barcode scanning).	Massively improves technician efficiency and data accuracy from the field.	Low. The API-first architecture was designed for this. Requires mobile app development resources.
Mid-Term	Native Mobile App for Customers	A native app for customers to manage their accounts, pay bills, check for outages, and run speed tests. Can also be used for push notifications.	Increases customer engagement and self-service rates. Strengthens brand presence.	Low. Also leverages the existing REST API.
Mid-Term	Customizable Dashboards & Reports	Allow users to build, save, and share their own custom dashboards and reports by selecting from a library of data sources and visualizations.	Empowers power-users and managers to get the exact data they need without requesting new development.	Medium. Requires a more advanced backend reporting engine and a complex frontend UI for the builder.
Long-Term	Customer-Facing Network Tools	Expose advanced (but simplified) network status information to customers in their portal, such as historical latency graphs and uptime reports, to increase transparency and trust.	Reduces support calls for "is the internet down?" and empowers savvy users. Builds brand loyalty.	Medium. Requires careful security review to ensure no sensitive network data is exposed.
3.4 Theme: Architectural Evolution
Goal: Ensure the underlying technology remains modern, scalable, and maintainable.

Horizon	Initiative	Description	Business Value	Architectural Impact
Near-Term	Transition to WebSockets	Fully transition the in-app notification and real-time monitoring UIs from polling to a full WebSocket-based implementation for true real-time updates.	Improves UI responsiveness and reduces unnecessary HTTP request load on the backend.	Low-Medium. Requires implementing and scaling a WebSocket server (e.g., Laravel Reverb, Soketi).
Mid-Term	Data Warehouse Implementation	Formally implement a separate data warehouse (e.g., Redshift, BigQuery) and a robust ETL pipeline. All complex analytical reporting would run against the DW.	Dramatically improves analytical query performance and completely isolates reporting workloads from the production OLTP database.	Medium. A standard evolution for growing applications. Requires data engineering expertise.
Mid-Term	Selective Microservice Extraction	Identify a module within the Modular Monolith that has become a performance bottleneck or requires independent scaling (e.g., the NMS polling service) and extract it into its own microservice.	Improves scalability and allows for independent deployment of that specific component.	High. This is the first step toward a microservices architecture. It introduces distributed system complexity (service discovery, inter-service communication). The Modular Monolith design makes this process much safer.
Long-Term	Full Event Sourcing/CQRS	For hyper-scale or extreme auditability requirements, re-architect a core domain (like Billing or Inventory) to use an Event Sourcing pattern with Command Query Responsibility Segregation (CQRS).	Provides a perfect, replayable audit log of all state changes and allows for highly optimized, separate read and write models.	Very High. This is a paradigm shift in application architecture and represents a massive undertaking. It is only justified by extreme scale or complex business requirements.
