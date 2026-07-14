Document 24: Logging Strategy
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the logging strategy for the SkyFi Networks ISP Management System. It defines the standards for what to log, the different log levels, the format of log messages, and the infrastructure for collecting, storing, and analyzing logs.

The goal is to establish a comprehensive and structured logging practice that:

Provides deep visibility into the application's runtime behavior.
Facilitates efficient debugging and troubleshooting of non-exceptional issues.
Creates an auditable trail of significant business events.
Enables performance monitoring and operational intelligence.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Define the logging strategy, standards, and required infrastructure.
Backend Developers	Instrument the code with appropriate log statements at the correct levels.
Operations / DevOps	Implement and maintain the centralized logging infrastructure (e.g., ELK stack, Datadog). Configure log shipping, parsing, and retention policies.
Security Team	Review the logging strategy to ensure sensitive data is not logged and that security-relevant events are captured.
3.0 Logging Philosophy: Structured, Centralized, and Actionable
Structured Logging: All log entries must be written in a machine-readable format, specifically JSON. Unstructured, plain-text logs are difficult to parse, filter, and analyze at scale.
Centralized Collection: Logs from all application instances, servers, and services must be aggregated into a single, centralized logging platform. Developers should never need to SSH into a server to read a log file.
Actionable Information: Logs are not just for recording events; they are for answering questions. "What happened?", "Why did it happen?", "Who did it?", and "How long did it take?". Every log message should be written with a potential future question in mind.
Log Levels as a Signal: Use log levels consistently to signal the severity and importance of an event, allowing for effective filtering and alerting.
4.0 Log Levels (Following RFC 5424)
We will adopt the standard syslog severity levels. Using these consistently is crucial for filtering and alerting.

Level	Keyword	When to Use	Example
DEBUG	debug	Detailed information for developers during active troubleshooting. Disabled in production by default.	A full API request/response body; the result of every step in a complex workflow.
INFO	info	Normal, significant application events. The "heartbeat" of the application.	User login success; invoice generation started/completed; service provisioned.
NOTICE	notice	Normal but significant conditions. Less common, can be used for events that are unusual but not errors.	A service plan was deprecated; a user changed their email address.
WARNING	warning	Indicates a potential problem or an unexpected event that is not a critical error but should be monitored.	Failed login attempt; API call to an external service timed out but was successfully retried; MikroTik router is unresponsive.
ERROR	error	A runtime error that prevented a specific operation from completing but did not crash the application.	An ExternalServiceException that was caught; a database query failed due to a deadlock.
CRITICAL	critical	A severe error that requires immediate attention and may impact overall system stability.	The application cannot connect to the database; the central message queue is down.
ALERT	alert	An action must be taken immediately. This level is typically reserved for alerts sent directly to operators.	An unhandled exception that crashed a critical background process.
EMERGENCY	emergency	The system is unusable.	(Rarely used in application code).
Production Default Level: In the production environment, the minimum logging level will be set to INFO. DEBUG logs will not be written to disk to avoid performance overhead and excessive "noise."

5.0 Standard Log Message Format (JSON)
Every log entry must be a single line of JSON containing a standard set of fields.

Standard Fields (Present in every log message):

Field	Type	Description
timestamp	String (ISO 8601)	The exact time the event occurred (e.g., 2023-10-27T10:00:00.123Z).
level	String	The log level keyword (e.g., info, error).
message	String	A short, human-readable summary of the event.
service	String	The name of the application (skyfi-api).
hostname	String	The server/container hostname where the log originated.
trace_id	String	A unique ID that correlates all logs within a single HTTP request or job execution.
Contextual Fields (Added as needed):

user_id: The ID of the authenticated user who initiated the action.
customer_id: The ID of the customer being acted upon.
invoice_id: The ID of the invoice involved.
duration_ms: The time taken for an operation to complete.
exception: The class name of a caught exception.
stack_trace: The full stack trace for an ERROR level or higher log.
request: An object containing HTTP request details (method, url, ip_address).
response: An object containing HTTP response details (status_code).
Example Log Entries:

INFO Log (User Login):

JSON

{
  "timestamp": "2023-10-27T10:01:30.500Z",
  "level": "info",
  "message": "User logged in successfully.",
  "service": "skyfi-api",
  "hostname": "app-server-01",
  "trace_id": "a1b2c3d4-e5f6-7890-1234-567890abcdef",
  "user_id": 123,
  "request": {
    "method": "POST",
    "url": "/api/v1/auth/login",
    "ip_address": "123.45.67.89"
  }
}
ERROR Log (MikroTik Provisioning Failure):

JSON

{
  "timestamp": "2023-10-27T11:15:05.800Z",
  "level": "error",
  "message": "Failed to provision PPPoE user.",
  "service": "skyfi-api",
  "hostname": "job-worker-03",
  "trace_id": "b2c3d4e5-f6a7-8901-2345-67890abcdef1",
  "user_id": 45, // The admin who triggered the activation
  "customer_id": 5678,
  "service_id": 9012,
  "router_id": 3,
  "router_ip": "10.1.1.1",
  "exception": "MikroTikConnectionException"
}
6.0 Logging Infrastructure
Technology Stack (ELK): We will use the ELK Stack (Elasticsearch, Logstash, Kibana) or a managed equivalent (e.g., AWS OpenSearch, Datadog, Logz.io).
Elasticsearch: A powerful search and analytics engine for storing and indexing the JSON logs.
Logstash (or Fluentd/Filebeat): The log shipping agent. It will run on each application server, tail the application log files, parse the JSON, and forward it to Elasticsearch.
Kibana: The web interface for searching, visualizing, and creating dashboards from the log data.
Logging Flow Diagram:

mermaid

graph TD
    subgraph "Application Servers (EC2)"
        App1[PHP App on Server 1] --> LogFile1[/var/log/app.log]
        App2[PHP App on Server 2] --> LogFile2[/var/log/app.log]
    end

    subgraph "Log Shippers"
        Shipper1[Filebeat on Server 1] --> Logstash
        Shipper2[Filebeat on Server 2] --> Logstash
    end
    
    subgraph "Centralized Logging Platform"
        Logstash -- "Parses & Enriches" --> Elasticsearch[Elasticsearch Cluster]
        Elasticsearch <--> Kibana[Kibana UI]
    end

    subgraph "Developers & Ops"
        User[Dev/Ops User]
    end

    LogFile1 -- Tailed by --> Shipper1
    LogFile2 -- Tailed by --> Shipper2

    User -- "Searches & Creates Dashboards" --> Kibana
7.0 Implementation Details
PHP Logger: A logging library that supports the PSR-3 standard (e.g., Monolog) will be used. It will be configured with a JsonFormatter.
Trace ID: A middleware will generate a unique trace_id for every incoming HTTP request. This ID will be attached to the logging context and passed in headers to any downstream services, allowing us to trace a single user action across the entire system. For background jobs, a trace_id will be generated when the job starts.
Log Rotation: Log files on the application servers must be rotated daily to prevent them from filling up the disk. The log shipper will be configured to handle rotated files.
Log Retention: A data retention policy will be established in Elasticsearch. For example:
DEBUG logs (in staging): Retain for 7 days.
INFO and NOTICE: Retain for 30 days.
WARNING and ERROR: Retain for 90 days.
CRITICAL and ALERT: Retain for 1 year.
8.0 What NOT to Log
Security is paramount. Under no circumstances should the following sensitive information be written to logs:

Passwords (in plaintext).
API Keys, tokens, or secrets.
Full credit card numbers or CVC codes.
Personally Identifiable Information (PII) unless absolutely necessary for debugging, and even then, it should be considered for masking or encryption.
Raw session data.
A "denylist" filter will be configured in the logging library to automatically scrub fields with keys like password, token, api_key, etc., from the log context before they are written.

9.0 Risks
| Risk | Description | Mitigation Strategy |
| :--- | :--- |
| Excessive Logging Volume | Logging too much, especially at the INFO level, can incur significant costs for storage and processing and make it hard to find important events. | Be intentional about what is logged at the INFO level. Log the start and end of significant business transactions, not every minor step within them. Use DEBUG for verbose logging and keep it disabled in production. |
| Sensitive Data Leakage| A developer accidentally logs a sensitive variable. | Use the centralized "denylist" filter as a safety net. Conduct regular code reviews with a focus on what is being added to the log context. Static analysis tools can also be configured to search for sensitive key names in log statements. |
| Performance Impact | Writing logs, especially to a slow disk or over the network, can impact application performance. | Log to a fast local disk asynchronously. The Filebeat agent will handle the shipping. Avoid logging inside tight loops. Keep the JSON payload reasonably sized. |
| Loss of Logs | If the centralized logging service is down, logs from the application servers might be lost. | Configure Filebeat with a buffer. It will hold logs on disk if the destination (Logstash/Elasticsearch) is unavailable and send them once it comes back online. |