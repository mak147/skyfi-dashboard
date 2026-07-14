Document 12: API Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the REST API that will serve as the backbone of the SkyFi Networks ISP Management System. It defines the design principles, standards, and conventions that all API endpoints must follow.

The purpose is to create a consistent, predictable, and easy-to-use API that decouples the client (React SPA, mobile apps) from the server-side business logic. This document serves as a binding contract for both frontend and backend developers.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Define and own the API architecture. Ensure all new endpoints conform to these standards.
Backend Developers	Implement API endpoints according to the defined standards, including URL structure, response formats, and status codes.
Frontend Developers	Consume the API according to the contract. Provide feedback on the API's usability and consistency.
QA Engineers	Use the API specification to write automated integration and end-to-end tests.
3.0 Goals
Consistency: All endpoints should look and feel the same, making the API predictable and easy to learn.
Clarity: The API should be self-documenting to a large extent, with clear resource naming and standard HTTP methods.
Statelessness: Adhere to the principles of REST by ensuring every request from a client contains all the information needed to process it. The server will not hold any client session state.
Client-Agnosticism: Design an API that can be consumed by any HTTP-capable client, not just our React SPA. This is key for future mobile app development (NFR-MAIN-004).
Efficiency: Allow clients to fetch the data they need with a reasonable number of requests, providing mechanisms for filtering, sorting, and including related data.
4.0 Architectural Style: REST (Representational State Transfer)
The API will be designed following the principles of REST over HTTP/S.

Resources: Everything is a resource (e.g., a customer, an invoice). Resources are identified by URIs.
Standard HTTP Methods: We will use standard HTTP verbs to operate on these resources.
Stateless Communication: Each request is independent.
JSON Data Format: All data exchange between the client and server will use the JSON format.
Justification: REST is the de facto industry standard for web APIs. It is mature, well-understood by developers, and supported by a vast ecosystem of tools and libraries. It perfectly aligns with our goal of a stateless, client-agnostic backend.

5.0 API Standards and Conventions
5.1 Versioning
All API endpoints must be versioned via the URL. This is critical for managing changes without breaking existing clients.

Format: /api/v{version_number}/...
Example: https://api.skyfinetworks.com/api/v1/customers
Justification: URL versioning is the most explicit and straightforward method. It avoids ambiguity and ensures that router configurations and client-side code are clear about which version they are targeting.

5.2 URL Structure & Naming
Resources: Use plural nouns to denote a resource collection (customers, invoices).
Identifiers: Use the resource's id in the path to identify a specific instance.
Nesting: Nest resources only one level deep to represent a direct parent-child relationship. Deeper nesting leads to complex and unwieldy URLs.
Casing: Use kebab-case for multi-word resource names for readability (e.g., service-plans).
Description	Method	URL
Get a list of customers	GET	/api/v1/customers
Create a new customer	POST	/api/v1/customers
Get a single customer	GET	/api/v1/customers/{id}
Update a customer (full)	PUT	/api/v1/customers/{id}
Update a customer (partial)	PATCH	/api/v1/customers/{id}
Delete a customer	DELETE	/api/v1/customers/{id}
Get all invoices for a customer	GET	/api/v1/customers/{id}/invoices
Get a specific invoice	GET	/api/v1/invoices/{id}
5.3 HTTP Methods (Verbs)
Use the standard HTTP methods to represent CRUD (Create, Read, Update, Delete) operations.

Method	Action	Example	Idempotent?
GET	Retrieve a resource or a collection of resources.	GET /customers	Yes
POST	Create a new resource.	POST /customers	No
PUT	Update an existing resource (replaces the entire resource).	PUT /customers/{id}	Yes
PATCH	Partially update an existing resource.	PATCH /customers/{id}	No
DELETE	Delete a resource (or soft-delete).	DELETE /customers/{id}	Yes
Note on Idempotency: An operation is idempotent if making the same request multiple times has the same effect as making it once. This is an important property for clients to build reliable retry logic.

5.4 HTTP Status Codes
The API will use standard HTTP status codes to indicate the outcome of a request. This is essential for client-side error handling.

Code	Meaning	When to Use
200 OK	The request was successful.	For successful GET, PUT, PATCH requests.
201 Created	The resource was successfully created.	For successful POST requests. The Location header will point to the new resource.
204 No Content	The request was successful, but there is no data to return.	For successful DELETE requests.
400 Bad Request	The request was malformed (e.g., invalid JSON, missing fields).	When validation fails. The response body will contain details.
401 Unauthorized	The request requires authentication, but none was provided or it was invalid.	When the JWT is missing, expired, or invalid.
403 Forbidden	The authenticated user does not have permission to perform the action.	When RBAC checks fail.
404 Not Found	The requested resource does not exist.	When requesting a resource by an ID that doesn't exist.
422 Unprocessable Entity	The request was well-formed, but contained semantic errors.	When server-side validation rules fail (e.g., email already exists).
500 Internal Server Error	A generic, unexpected error occurred on the server.	For unhandled exceptions. This should be a rare event and trigger an alert.
5.5 Standard JSON Response Formats
To ensure consistency, all API responses will follow a standard structure. We will adopt a structure inspired by the JSON:API specification for clarity and to support common features.

Successful Single Resource Response (200 OK)

JSON

{
  "data": {
    "type": "customers",
    "id": "123",
    "attributes": {
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com",
      "status": "active"
    },
    "relationships": {
      "services": {
        "links": {
          "related": "/api/v1/customers/123/services"
        }
      }
    },
    "links": {
      "self": "/api/v1/customers/123"
    }
  }
}
Successful Collection Response (200 OK)

JSON

{
  "data": [
    {
      "type": "customers",
      "id": "123",
      "attributes": { ... }
    },
    {
      "type": "customers",
      "id": "124",
      "attributes": { ... }
    }
  ],
  "links": {
    "self": "/api/v1/customers?page=2",
    "first": "/api/v1/customers?page=1",
    "prev": "/api/v1/customers?page=1",
    "next": "/api/v1/customers?page=3",
    "last": "/api/v1/customers?page=10"
  },
  "meta": {
    "current_page": 2,
    "from": 16,
    "last_page": 10,
    "per_page": 15,
    "to": 30,
    "total": 150
  }
}
Error Response (4xx or 5xx)

JSON

{
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Entity",
      "detail": "The email has already been taken.",
      "source": { "pointer": "/data/attributes/email" }
    }
  ]
}
Justification: This standardized format provides rich context. It clearly separates attributes from metadata and relationships, includes self-referential links (HATEOAS principle), and provides a consistent, structured way to handle errors, which is invaluable for client-side developers.

6.0 Common API Features
The API will support a standard set of features on all collection (GET /resource) endpoints via query parameters.

6.1 Filtering
Clients can filter collections based on attribute values.

Syntax: ?filter[attribute]=value
Example: GET /api/v1/invoices?filter[status]=overdue
6.2 Sorting
Clients can specify the sort order of a collection.

Syntax: ?sort=attribute (ascending) or ?sort=-attribute (descending).
Example: GET /api/v1/customers?sort=-created_at (returns newest customers first).
6.3 Pagination
All collection endpoints must be paginated to prevent overwhelming the server and client with huge datasets.

Syntax: ?page[number]=2&page[size]=25
Details: The API will use page-based pagination. The response will include links and meta objects as shown in the collection response example above.
6.4 Including Related Resources (Compound Documents)
To solve the "N+1 problem" and reduce the number of HTTP requests, clients can request related resources to be included in the primary response.

Syntax: ?include=relationship_name
Example: GET /api/v1/invoices/456?include=customer,payments
Response: The response will contain a top-level included array with the requested resource objects.
JSON

{
  "data": {
    "type": "invoices",
    "id": "456",
    "attributes": { ... },
    "relationships": {
      "customer": { "data": { "type": "customers", "id": "123" } }
    }
  },
  "included": [
    {
      "type": "customers",
      "id": "123",
      "attributes": { ... }
    }
  ]
}
Enterprise Recommendation: This feature is critical for frontend performance. Allowing the client to specify exactly what related data it needs for a given view avoids dozens of subsequent API calls.

7.0 API Documentation
The API will be documented using the OpenAPI 3.0 Specification.

Generation: Backend code comments (docblocks) will be used to automatically generate the openapi.json file. This ensures the documentation is always in sync with the code.
UI: A tool like Swagger UI or Redoc will be deployed to provide an interactive, human-readable documentation portal for developers.
8.0 Risks
Risk	Description	Mitigation Strategy
Inconsistent Implementation	Developers deviate from the standards, leading to a confusing and unpredictable API.	Strict code reviews are the primary defense. A standardized API resource generation tool should be used. Automated linting can check for some conventions.
"Chatty" API	Clients need to make too many requests to render a single view, leading to poor performance.	The ?include parameter is the primary mitigation. Design endpoints thoughtfully around UI needs. In rare, performance-critical cases, a dedicated, non-RESTful endpoint that aggregates data for a specific view may be created.
Over-fetching	The default API response includes many fields the client doesn't need, wasting bandwidth.	Implement a Sparse Fieldsets feature (?fields[resource]=field1,field2). This allows the client to request only the specific attributes it needs. This is considered a v1.1 or v2 feature but should be planned for.
Breaking Changes	A change to an endpoint breaks the client application.	Strict adherence to API versioning. Never introduce a breaking change within an existing version. Instead, create a new version (/api/v2/...) and deprecate the old one, providing a migration path and timeline.