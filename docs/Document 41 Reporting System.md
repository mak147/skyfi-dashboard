Document 41: Reporting System
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the Reporting System within the SkyFi Networks platform. It covers the design for generating, processing, and presenting various operational, financial, and network reports to authorized users.

The goal is to design a performant, flexible, and scalable reporting system that empowers stakeholders to:

Make data-driven decisions.
Monitor Key Performance Indicators (KPIs).
Analyze historical trends.
Fulfill financial and operational auditing requirements.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the reporting architecture, including the data aggregation strategies and query patterns.
Backend Developers	Implement the ReportingService, API endpoints, and the underlying database queries or data warehouse connections.
Frontend Developers	Build the UI for displaying report data in tables and charts, and for configuring report parameters (e.g., date ranges).
Department Heads / Stakeholders	Define the requirements for each report, including the necessary columns, filters, and calculations.
3.0 Architectural Strategy: Hybrid Approach
A single approach to reporting rarely fits all needs. We will adopt a hybrid strategy to balance real-time needs with the performance demands of complex analytics.

Direct OLTP Queries (For Operational Reports):

Description: Reports that require near real-time data and operate on a limited, filtered dataset will query the primary production database (OLTP).
Use Case: Viewing a list of all unpaid invoices for a specific customer; generating a work order list for a single technician for today.
Mitigation: These queries must be highly optimized and use a Read Replica of the database to avoid impacting the performance of the primary write database.
Snapshot/Aggregation Tables (For Analytical Reports):

Description: For complex, resource-intensive reports (like monthly recurring revenue trends), we will not query the raw transactional tables directly. Instead, a scheduled job will run during off-peak hours to pre-calculate and store the results in dedicated summary or "snapshot" tables.
Use Case: Generating an MRR chart for the last 12 months; calculating monthly churn rates.
Benefit: The user-facing report queries become incredibly fast, as they are reading from small, pre-aggregated tables instead of processing millions of invoice_items or services records on the fly.
Future Evolution: Data Warehouse
As the business scales, this hybrid model will evolve into a proper Data Warehouse (DW) and ETL (Extract, Transform, Load) process. The snapshot tables are a pragmatic first step towards this. Data would be regularly exported from the production OLTP database to a separate analytical database (e.g., Amazon Redshift, Google BigQuery) optimized for complex analytical queries. For v1.0, the snapshot table approach is sufficient.

Reporting Architecture Diagram:

mermaid

graph TD
    subgraph "Data Sources"
        A[Production DB (OLTP - Master)] --> B[Production DB (Read Replica)]
        A --> C{Scheduled Job (ETL)}
        C --> D[Snapshot/Analytics Tables]
    end

    subgraph "Backend Reporting Service"
        E[ReportingService]
    end

    subgraph "API Layer"
        F[API Endpoints (/api/v1/reports/...)]
    end

    subgraph "Frontend"
        G[React UI (Tables & Charts.js)]
    end

    B -- "Real-time Operational Queries" --> E
    D -- "Fast Analytical Queries" --> E
    E --> F
    F --> G
4.0 Core Service and Implementation
4.1 ReportingService

Responsibility: To act as the central entry point for all reporting logic. It encapsulates the knowledge of where and how to get the data for a specific report.
Design: The service will have a dedicated method for each report.
getArAgingReport(asOfDate)
getMrrTrendReport(startDate, endDate)
getSubscriberChurnReport(period)
Implementation: Inside each method, the service decides which strategy to use.
For the A/R Aging report, it might run a direct, optimized query against the invoices table on the read replica.
For the MRR Trend report, it will run a simple SELECT * FROM monthly_mrr_snapshots WHERE date BETWEEN ? AND ?.
4.2 Scheduled Job for Aggregation (reports:generate-snapshots)

Trigger: Runs nightly during a low-traffic period.
Logic:
This job will contain the heavy, complex SQL queries.
Example for monthly_mrr_snapshots: It would query all active services and their plan prices for each month, sum them up, and insert a single row into the snapshot table (e.g., { month: '2023-10', mrr: 15234.50, active_subscribers: 350 }).
This job must be idempotent; running it multiple times for the same period should produce the same result.
5.0 Key Report Specifications
This section defines the initial set of critical reports.

5.1 Financial Reports

Monthly Recurring Revenue (MRR) Report:
Type: Analytical (uses snapshot table).
UI: A line or bar chart showing total MRR for each of the last 12-24 months. A data table below shows the raw numbers.
Metrics: MRR, New MRR, Churn MRR, Net New MRR, Active Subscribers.
Accounts Receivable (A/R) Aging Report:
Type: Operational (queries read replica).
UI: A data table summarizing outstanding invoice balances, bucketed by how long they are past due.
Columns: Customer Name, 0-30 Days, 31-60 Days, 61-90 Days, 90+ Days, Total Due.
Payments Collected Report:
Type: Operational.
UI: A filterable data table of all payments received within a specified date range.
Columns: Payment Date, Customer Name, Invoice #, Amount, Payment Method, Transaction ID.
5.2 Subscriber Reports

Subscriber Churn Report:
Type: Analytical (uses snapshot table).
UI: A chart showing monthly churn rate (both customer churn and revenue churn).
Metrics: Starting Subscribers, New Subscribers, Churned Subscribers, Ending Subscribers, Churn Rate %.
New Activations Report:
Type: Operational.
UI: A data table listing all new customer services activated within a date range.
Columns: Activation Date, Customer Name, Service Plan, Tower, Sales Rep.
5.3 Network Reports

Tower Capacity Report:
Type: Operational/Analytical.
UI: A data table listing all towers.
Columns: Tower Name, Total Capacity (theoretical), Current Subscribers, Utilization %, Link to Tower Details.
This report requires aggregating the number of active services per tower.
6.0 User Interface for Reports
Report Index Page (/reports): A landing page that lists all available reports, grouped by category (Financial, Subscriber, etc.). Each entry is a link to the specific report's page. Access is controlled by RBAC.
Report View Page (/reports/financial/mrr):
Header: Contains the report title and a set of common filter controls.
Filters: Every report page must have a Date Range Picker as the primary filter. Other filters could include Region, Service Plan, etc., depending on the report.
Export Button: A prominent button to export the current view.
Content Area: Displays the data in the most appropriate format (chart, table, or both).
Charts: Will be rendered using Chart.js. They must be interactive (e.g., tooltips on hover).
Tables: Will use the DataTable common component, providing sorting on the client side.
7.0 Exporting Functionality
Requirement: Users must be able to export report data for use in external tools like Excel.
Format: CSV (Comma-Separated Values) is the standard format.
Implementation:
The "Export" button on the frontend will make a special API call, e.g., GET /api/v1/reports/ar-aging?format=csv&asOfDate=....
The backend ReportingService will generate the full, unpaginated dataset for the report.
It will then format this data as a CSV string.
The API response will have a Content-Type header of text/csv and a Content-Disposition header that suggests a filename (e.g., attachment; filename="ar-aging-report-2023-10-27.csv").
The browser will automatically trigger a file download.
Asynchronous Exports: For extremely large datasets that could cause a request timeout, the export process will be asynchronous.
The user clicks "Export."
The API immediately returns a 202 Accepted response.
A background job is queued to generate the CSV file and store it temporarily (e.g., in S3).
When the job is complete, the user receives an in-app notification with a link to download the generated file.
8.0 Risks
Risk	Description	Mitigation Strategy
Performance Degradation	A complex, unoptimized report query runs directly against the production database, slowing down the entire application for all users.	This is the primary risk. The hybrid architecture is the mitigation. Strictly enforce the rule: long-running analytical queries must use pre-aggregated snapshot tables. All direct OLTP queries must target a read replica and be reviewed with EXPLAIN to ensure they use indexes properly.
Inaccurate Data	The logic in the ETL/snapshot job is flawed, leading to reports that are consistently incorrect.	The SQL queries used for aggregation must be treated as critical code. They need to be peer-reviewed, ideally by someone with strong data analysis skills. A reconciliation process should be built to compare the snapshot totals against a direct query of the source data for a small sample, ensuring they match.
Data "Staleness"	A manager makes a decision based on a report that is up to 24 hours out of date because it relies on a nightly snapshot.	This is a trade-off between performance and real-time accuracy. The UI must clearly label reports that use snapshot data, indicating the "Last Updated" time. For mission-critical metrics that need to be more real-time, a different strategy (like a materialized view or more frequent micro-batch updates) might be needed.
Memory Issues	Generating a large report or CSV export on the backend consumes too much memory and crashes the process.	Use streaming and chunking. When querying the database for an export, fetch records in batches (e.g., 1000 at a time) rather than loading the entire dataset into memory. Write to the CSV file stream line-by-line.
