Document 06: System Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document details the System Architecture for the SkyFi Networks ISP Management System. It defines the physical and logical infrastructure, including servers, networking, storage, and other third-party services, that are required to deploy, operate, and scale the software defined in the Software Architecture document.

The goal is to provide a comprehensive blueprint for the DevOps and Operations teams to provision, configure, and maintain the production, staging, and development environments. It ensures that the infrastructure is designed to meet the critical Non-Functional Requirements (NFRs) for availability, scalability, performance, and security.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Define and own the System Architecture. Ensure it aligns with the software architecture and business goals.
Operations / DevOps Lead	Implement, automate, and manage the infrastructure as defined in this document.
Security Team	Review and approve the architecture's security posture, including network configuration, firewalls, and access controls.
Finance Department	Review the proposed architecture for cost-effectiveness and budget planning (Cloud resource costs).
3.0 Goals
High Availability: Design an infrastructure with no single point of failure to meet the NFR-REL-001 (99.9% uptime) requirement.
Scalability: Create an environment that can automatically scale resources up or down based on demand, fulfilling NFR-SCAL-001 and NFR-SCAL-003.
Security: Implement a secure network topology that protects sensitive data and services from unauthorized access.
Cost-Effectiveness: Choose services and configurations that provide the best performance for the cost, leveraging cloud-native capabilities.
Maintainability: Design an infrastructure that is easy to monitor, manage, and update through automation (Infrastructure as Code).
4.0 Hosting Environment: Cloud-Native on AWS
The SkyFi Networks platform will be hosted on Amazon Web Services (AWS).

Architectural Justification:

Managed Services: AWS provides a rich ecosystem of managed services (e.g., RDS, ElastiCache, ALB) that offload significant operational burdens related to database management, caching, and load balancing. This allows the team to focus on the application, not the underlying infrastructure.
Scalability & Elasticity: AWS offers robust auto-scaling capabilities, allowing the system to dynamically adjust to traffic loads, which is more cost-effective and resilient than provisioning for peak capacity.
Global Reach & Reliability: AWS's global infrastructure provides the foundation for high availability, disaster recovery, and future expansion into new geographic regions.
Security & Compliance: AWS provides a secure-by-default environment and tooling that helps in achieving compliance certifications like PCI DSS and SOC 2.
5.0 Production Environment Architecture
The production environment will be deployed across multiple Availability Zones (AZs) within a single AWS Region for high availability. An AZ is a distinct data center with redundant power, networking, and cooling.

5.1 High-Level Production Diagram

mermaid

graph TD
    subgraph Internet
        User[Users / Customers]
        DNS[Cloudflare DNS/WAF/CDN]
    end

    subgraph "AWS Region"
        VPC[VPC: 10.0.0.0/16]
        
        subgraph "Public Subnet (AZ 1)"
            LB1[Application Load Balancer]
            NAT1[NAT Gateway]
        end
        
        subgraph "Public Subnet (AZ 2)"
            LB2[Application Load Balancer]
            NAT2[NAT Gateway]
        end

        subgraph "Private App Subnet (AZ 1)"
            ASG1[Auto Scaling Group]
            EC2_1a[EC2 Instance - PHP App]
            EC2_1b[EC2 Instance - PHP App]
        end

        subgraph "Private App Subnet (AZ 2)"
            ASG2[Auto Scaling Group]
            EC2_2a[EC2 Instance - PHP App]
            EC2_2b[EC2 Instance - PHP App]
        end
        
        subgraph "Private Data Subnet (AZ 1)"
            RDS_Master[(RDS MySQL - Master)]
            EC_1[(ElastiCache Redis)]
        end
        
        subgraph "Private Data Subnet (AZ 2)"
            RDS_Slave[(RDS MySQL - Read Replica/Standby)]
            EC_2[(ElastiCache Redis)]
        end
    end

    User --> DNS
    DNS --> LB1
    DNS --> LB2

    LB1 & LB2 --> ASG1
    LB1 & LB2 --> ASG2

    ASG1 --> EC2_1a
    ASG1 --> EC2_1b
    ASG2 --> EC2_2a
    ASG2 --> EC2_2b
    
    EC2_1a & EC2_1b --> RDS_Master
    EC2_2a & EC2_2b --> RDS_Master
    EC2_1a & EC2_1b --> EC_1
    EC2_2a & EC2_2b --> EC_2
    
    EC2_1a & EC2_1b --> NAT1
    EC2_2a & EC2_2b --> NAT2
    
    RDS_Master -- Replication --> RDS_Slave
    NAT1 & NAT2 --> Egress[Egress to Internet <br> (Payment Gateway, MikroTik API, etc.)]

    style VPC fill:#f9f9f9
5.2 Component Specification
Component	AWS Service	Configuration Details & Justification
DNS, CDN, WAF	Cloudflare	DNS: Manages all skyfinetworks.com records. CDN: Caches static assets (JS, CSS, images) at the edge, reducing load on our servers and improving global load times (NFR-PERF-002). WAF: Web Application Firewall provides a critical first line of defense against common attacks (SQLi, XSS) and DDoS mitigation (NFR-SEC-003).
Virtual Private Cloud	AWS VPC	A logically isolated section of the AWS cloud. All resources will be launched within this VPC. We will use a standard multi-AZ subnet layout (Public for load balancers, Private for application and data tiers) to enforce network segmentation.
Load Balancer	AWS Application Load Balancer (ALB)	Spans multiple AZs. Distributes incoming HTTP/S traffic across the EC2 instances. It will handle SSL/TLS termination, ensuring all internal traffic can be HTTP for simplicity while all external traffic is encrypted (NFR-SEC-001). It also manages health checks (NFR-MAIN-005).
Application Servers	AWS EC2 + Auto Scaling Group	EC2: Virtual servers running a Linux OS (e.g., Amazon Linux 2) with PHP-FPM and a web server (Nginx). Auto Scaling Group: Automatically adjusts the number of EC2 instances based on CPU utilization or request count. This directly addresses NFR-SCAL-003 (Horizontal Scalability) and NFR-REL-001 (High Availability) by replacing failed instances.
Database	AWS RDS for MySQL	A managed relational database service. Deployed in a Multi-AZ configuration. This creates a synchronous standby replica in a different AZ. In case of primary DB failure, RDS automatically fails over to the standby, fulfilling our RTO/RPO targets (NFR-REL-002, NFR-REL-003). Daily automated snapshots and point-in-time recovery will be enabled.
Cache	AWS ElastiCache for Redis	An in-memory key-value store used for caching database query results, session information (if needed for specific integrations), and rate-limiting data. Reduces load on the database and improves performance (NFR-PERF-001).
Static Asset Storage	AWS S3	The React SPA's build artifacts (JS, CSS files) will be stored in an S3 bucket and served via Cloudflare CDN. User-generated content (e.g., uploaded documents) will also be stored in S3, not on the EC2 instances' local filesystems.
Egress Traffic	AWS NAT Gateway	Placed in the Public Subnet, this allows instances in the Private Subnets to initiate outbound traffic to the internet (e.g., to call payment gateway APIs or connect to MikroTik routers) without allowing inbound traffic to be initiated from the internet. This is a critical security measure.
Logging & Monitoring	AWS CloudWatch	Collects logs, metrics, and events from all AWS resources. CloudWatch Alarms will be configured to trigger notifications (via SNS) or auto-scaling actions based on thresholds (e.g., CPU > 70%). Logs will be streamed to a dedicated logging service for analysis (NFR-MAIN-003).
5.3 Network & Security Configuration
Security Groups (SGs): Act as virtual firewalls for EC2 instances. Rules will be strictly "least privilege."
ALB SG: Allows inbound traffic on port 443 (HTTPS) from Cloudflare's IP ranges only.
App Server SG: Allows inbound traffic on port 80 (HTTP) only from the ALB SG. Allows outbound traffic to the Database SG, ElastiCache, and the NAT Gateway.
Database SG: Allows inbound traffic on port 3306 (MySQL) only from the App Server SG.
Network ACLs (NACLs): A stateless, secondary layer of defense at the subnet level. Will be used for broad "deny" rules (e.g., block known malicious IP ranges).
IAM Roles: EC2 instances will be assigned IAM Roles to grant them permissions to access other AWS services (like S3) without storing long-lived credentials on the instances themselves.
6.0 Staging & Development Environments
Staging Environment:

Purpose: To test new code in a production-like environment before release. Used for final QA, UAT, and performance testing.
Architecture: A scaled-down replica of the production environment within the same AWS account and VPC, but with smaller instance types and a single-AZ RDS instance to manage costs. It will be completely isolated from production.
Data: Will use a sanitized and anonymized snapshot of the production database.
Development Environment:

Purpose: For individual developers to build and test their features.
Architecture: To ensure consistency and reduce setup time, the development environment will be managed via Docker Compose. A docker-compose.yml file will be provided in the project repository to spin up local containers for PHP, Nginx, MySQL, and Redis, mirroring the production stack.
7.0 Dependencies
External Network Access: The production environment's NAT Gateways must have stable internet connectivity and their public IP addresses must be whitelisted by external services (e.g., Payment Gateway, specific MikroTik routers if IP-based ACLs are used).
Domain Name Ownership: SkyFi Networks must own and have control over the skyfinetworks.com domain to configure Cloudflare.
8.0 Future Expansion
Multi-Region Deployment: For disaster recovery or to serve a global customer base with lower latency, the entire architecture can be replicated in a second AWS Region. AWS Route 53 latency-based routing could direct users to the nearest region, and RDS cross-region replicas could be used for data replication.
Containerization in Production: As the application matures, the EC2-based deployment can be migrated to a container orchestration platform like Amazon ECS or EKS (Kubernetes). This would improve deployment efficiency, resource density, and portability. The current 12-factor app design (stateless, configuration in environment) makes this a natural evolution.
9.0 Risks
Risk	Mitigation
Cloud Cost Overruns	Uncontrolled scaling or selection of oversized resources can lead to high costs.
Security Misconfiguration	An improperly configured Security Group or IAM policy could expose the system to attack.
Vendor Lock-in	Heavy reliance on AWS-specific services could make a future move to another cloud provider difficult and costly.