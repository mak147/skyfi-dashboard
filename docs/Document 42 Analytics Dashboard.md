Document 42: Analytics Dashboard
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the main Analytics Dashboard, which serves as the primary landing page after a user logs in. The dashboard provides a high-level, visual summary of the most critical Key Performance Indicators (KPIs) and operational metrics, tailored to the user's role.

The goal is to design a dashboard that is:

Role-Aware: Displays the information most relevant to the logged-in user's job function.
Actionable: Not only displays data but also provides direct links to take action.
Visual: Uses charts and summary "stat cards" for quick comprehension.
Performant: Loads quickly and provides a near real-time snapshot of the business.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the dashboard's modular architecture, data fetching strategy, and caching mechanisms.
Backend Developers	Create the dedicated, optimized API endpoints to provide data for each dashboard widget.
Frontend Developers	Build the dashboard layout grid and the individual "widget" components that display the data.
Product Owner / Stakeholders	Define the specific KPIs and widgets that are most important for each user role.
3.0 Architectural Strategy: Modular Widgets and a Dedicated API
The dashboard will not be a monolithic page. It will be a container for a collection of independent "widgets" or "cards."

Widget-Based Architecture: The dashboard is a grid. Each element on the grid (e.g., "Active Subscribers," "MRR," "Open Tickets") is a self-contained React component.
Dedicated Dashboard API: A single, highly optimized backend endpoint will be created to serve the data for the entire dashboard in one request. This is crucial for performance.
Role-Based Configuration: The backend will determine which widgets to return data for based on the user's role, and the frontend will dynamically render the appropriate widgets.
Heavy Caching: The data for the dashboard is a prime candidate for caching. The results of the dashboard API endpoint will be heavily cached on the backend (e.g., in Redis) with a short TTL (Time To Live), such as 5-15 minutes.
Dashboard Data Flow:

mermaid

flowchart TD
    A[User logs in or navigates to /dashboard] --> B[Frontend makes a single API call: GET /api/v1/dashboard]
    
    subgraph "Backend API"
        B --> C{DashboardController}
        C --> D[Check user's role]
        D --> E[DashboardService.getDataForRole(role)]
        E --> F{Is there a valid cache entry for this role's dashboard?}
        F -- Yes --> G[Return cached JSON data]
        F -- No --> H[Generate fresh dashboard data]
        H --> I[Store new data in Redis Cache with 5-min TTL]
        I --> J[Return fresh JSON data]
    end

    subgraph "Frontend Dashboard UI"
        G --> K
        J --> K[Receive single JSON object with data for all widgets]
        K --> L[Render DashboardGrid component]
        L --> M[Map over data keys and render individual Widget components]
        M --> W1[StatCardWidget: Active Subscribers]
        M --> W2[ChartWidget: Revenue This Month]
        M --> W3[ListWidget: My Open Tickets]
    end
Justification:

Performance: A single API call is far more efficient than having each of the 10-15 widgets make its own individual API call on page load.
Caching: Caching the consolidated result is simple and highly effective. Since dashboard data doesn't need to be second-by-second accurate, a 5-minute cache provides a huge performance boost while still feeling "live."
Maintainability: The logic for each widget's data is co-located in the DashboardService, making it easy to manage. The frontend is just a "dumb" renderer of the data it receives.
4.0 DashboardService and API Design
DashboardService:

This service will contain methods like getFinancialWidgets(), getSupportWidgets(), getNetworkWidgets().
The main getDataForRole(role) method will call the appropriate methods based on the role and merge the results into a single large object.
Each data-gathering method will use optimized, specific SQL queries. These queries are prime candidates for targeting a read replica database.
API Response (GET /api/v1/dashboard):
The API will return a JSON object where the keys correspond to specific widgets.

JSON

{
  "stats": {
    "active_subscribers": { "value": 1250, "change": "+12 since last month" },
    "mrr": { "value": 52480.00, "change": "+2.5%" },
    "open_tickets": { "value": 15, "priority": "3 urgent" }
  },
  "revenue_this_month": {
    "labels": ["Week 1", "Week 2", "Week 3", "Week 4"],
    "datasets": [{ "label": "Revenue", "data": [12000, 13500, 11500, 15480] }]
  },
  "my_assigned_tickets": [
    { "id": 1, "subject": "Internet is slow", "customer": "John Doe" },
    { "id": 2, "subject": "Billing question", "customer": "Jane Smith" }
  ],
  "tower_status": {
    "online": 15,
    "offline": 1,
    "maintenance": 0
  }
}
5.0 Widget Library
The frontend will have a set of generic dashboard widget components.

StatCardWidget:
Purpose: Displays a single, large KPI value with a secondary comparison metric.
Props: title, value, change, icon, color.
Example: "Active Subscribers," "MRR."
ChartWidget:
Purpose: Displays a chart. A wrapper around Chart.js.
Props: title, type: 'line' | 'bar' | 'doughnut', data (in Chart.js format), options.
Example: "Revenue This Month," "Ticket Volume."
ListWidget:
Purpose: Displays a short, scannable list of items.
Props: title, items: { id, primaryText, secondaryText }[], linkToMore.
Example: "My Assigned Tickets," "Recent Payments."
GaugeWidget:
Purpose: Displays a value as a percentage of a total, often as a radial gauge.
Props: title, value, max, unit.
Example: "Tower Capacity Utilization."
6.0 Role-Based Dashboard Configurations
The DashboardService will assemble different sets of widgets for each role.

6.1 Company Owner / Super Admin Dashboard:

StatCard: MRR
StatCard: Active Subscribers
StatCard: Monthly Churn %
ChartWidget: Revenue vs. Expenses (Last 6 Months)
ChartWidget: Subscriber Growth Trend
ListWidget: High-Priority Open Tickets
GaugeWidget: System-wide Network Health
6.2 Customer Support Agent Dashboard:

StatCard: My Open Tickets
StatCard: Unassigned Tickets in Queue
StatCard: Average First Response Time
ListWidget: My Recently Updated Tickets
ListWidget: Recent Customer Activations
ChartWidget: Ticket Inflow vs. Outflow (Today)
6.3 Network Engineer Dashboard:

StatCard: Routers Online
StatCard: Towers with Alarms
StatCard: Active PPPoE Sessions
ChartWidget: System-wide Bandwidth Throughput (Real-time)
ListWidget: Devices with High CPU/Memory
ListWidget: Recent Provisioning Failures
7.0 Implementation Details
Frontend Layout: The dashboard will be built using CSS Grid to allow for flexible and responsive arrangement of widgets.
Customization (Future): A v2 feature will be to allow users to customize their own dashboards by adding, removing, and rearranging widgets from a predefined library. This would involve saving the user's dashboard layout configuration in the database.
Drill-Down: Every widget should be a gateway to more detailed information.
Clicking the "Active Subscribers" StatCard should navigate the user to the full Customer List page (/customers).
Clicking an item in the "My Assigned Tickets" ListWidget should navigate to that specific ticket's detail page (/tickets/{id}).
8.0 Risks
Risk	Description	Mitigation Strategy
Slow Dashboard Load Time	The single dashboard API endpoint becomes slow as more widgets and complex queries are added.	Vigilant query optimization is key. The SQL queries in the DashboardService must be highly performant and use appropriate indexes. The backend caching strategy is also a critical mitigation. If a single widget's query is exceptionally slow, consider moving it to its own async API call so it doesn't block the rest of the dashboard from rendering.
"Stale" Data	The 5-minute cache means the data isn't perfectly real-time, which might be an issue for some operational roles.	This is a trade-off. For most KPIs, 5 minutes is acceptable. For widgets that truly need real-time data (e.g., a Network Engineer's "Routers Online" status), that specific widget can be architected to make its own separate, uncached API call or even connect to a WebSocket for live updates. The hybrid model allows for this flexibility.
Information Overload	The dashboard becomes a cluttered "wall of numbers" that is hard to interpret.	Less is more. The design must be curated and focused. Each widget should answer a specific, important question for that user's role. Use clear visual hierarchy, whitespace, and trends (+2.5%) over just raw numbers.
Inaccurate KPIs	The business logic for calculating a KPI (e.g., MRR, Churn) is flawed.	The calculation logic must be centralized in a single service method and have thorough unit tests. The definitions for all KPIs must be clearly documented and approved by business stakeholders to ensure everyone is measuring the same thing.