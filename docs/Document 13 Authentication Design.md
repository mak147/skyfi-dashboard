Document 13: Authentication Design
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the authentication mechanism for the SkyFi Networks ISP Management System. It details how users (both staff and customers) prove their identity to the system to gain access. The focus is on securing the API, which is consumed by the React SPA and any future clients.

The goal is to design a secure, stateless, and scalable authentication system that aligns with modern web application best practices and meets the security requirements outlined in NFR-SEC-004 and NFR-SEC-005.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Define and own the authentication design and security principles.
Backend Developers	Implement the authentication endpoints, JWT generation/validation logic, and middleware.
Frontend Developers	Implement the login form, token storage, and mechanism for attaching the token to all API requests.
Security Team	Review and approve the cryptographic choices, token lifecycle, and overall security posture of the design.
3.0 Goals
Security: Implement a strong, industry-standard authentication protocol that protects user credentials and prevents unauthorized access.
Statelessness: Ensure the authentication mechanism does not require the server to store session state, supporting horizontal scalability (NFR-SCAL-003).
Performance: Provide a fast and efficient way to verify user identity on every API request.
Good User Experience: Enable a "remember me" functionality and a seamless user experience without sacrificing security.
Client-Agnosticism: The design must work for web browsers, mobile applications, and third-party server-to-server integrations.
4.0 Chosen Architecture: JWT with Refresh Tokens
We will implement a token-based authentication system using JSON Web Tokens (JWT), following the Refresh Token Pattern.

4.1 Core Components

Access Token (JWT): A short-lived token containing user identity and permissions (claims). It is sent with every API request to authorize the user.
Refresh Token: A long-lived, opaque, and single-use token. Its sole purpose is to obtain a new Access Token when the old one expires. It is stored securely by the client and in the database.
4.2 Architectural Justification

Why JWT? JWTs are a compact, self-contained, and cryptographically signed standard (RFC 7519) for transmitting information between parties. Being self-contained (they carry user data within them), they enable a truly stateless backend. The server can validate the token's signature without needing to make a database call on every request, which is a significant performance benefit.
Why the Refresh Token Pattern? Using only a long-lived Access Token is a security risk; if stolen, it grants access for its entire lifetime. Using only a short-lived Access Token forces the user to log in frequently, which is poor UX. The Refresh Token pattern provides the best of both worlds:
Security: The Access Token is short-lived (e.g., 15 minutes), so the window of opportunity for a stolen token is small.
User Experience: The long-lived Refresh Token (e.g., 30 days) can be used silently in the background by the frontend to get a new Access Token without interrupting the user.
Control: Refresh Tokens are stored in the database, giving the server the ability to revoke a user's session immediately by invalidating their refresh tokens (e.g., on password change or manual logout).
5.0 Authentication Workflow
This diagram illustrates the complete login and subsequent request flow.

mermaid

sequenceDiagram
    participant C as Client (React SPA)
    participant S as Server (PHP API)
    participant DB as Database

    %% Login Flow %%
    C->>S: POST /api/v1/auth/login (email, password)
    S->>DB: Find user by email, verify password hash
    Note right of S: Password must be hashed with Argon2id
    DB-->>S: User record (if valid)
    S->>S: Generate short-lived Access Token (JWT)
    S->>S: Generate long-lived, secure Refresh Token (random string)
    S->>DB: Store hashed Refresh Token in `refresh_tokens` table with user_id and expiry
    S-->>C: 200 OK (accessToken, refreshToken)

    %% Secure Token Storage on Client %%
    C->>C: Store Access Token in memory (e.g., Redux state)
    C->>C: Store Refresh Token in secure, httpOnly cookie

    %% Authenticated Request Flow %%
    loop Every API call
        C->>S: GET /api/v1/customers (Authorization: Bearer <accessToken>)
        S->>S: Validate JWT signature and expiry (no DB call)
        S-->>C: 200 OK (data)
    end

    %% Access Token Expiration & Refresh Flow %%
    C->>S: GET /api/v1/invoices (Authorization: Bearer <expiredAccessToken>)
    S->>S: Validate JWT -> Fails (expired)
    S-->>C: 401 Unauthorized (error: "token_expired")
    
    C->>S: POST /api/v1/auth/refresh (sends httpOnly cookie with refreshToken)
    S->>DB: Find Refresh Token in `refresh_tokens` table. Verify it's valid, not expired, and not used.
    DB-->>S: Token record (if valid)
    S->>S: Generate new short-lived Access Token
    S->>DB: (Optional but recommended) Invalidate old Refresh Token and issue a new one (Rotation)
    S-->>C: 200 OK (newAccessToken)
    
    C->>C: Store new Access Token in memory
    C->>S: Retry: GET /api/v1/invoices (Authorization: Bearer <newAccessToken>)
    S-->>C: 200 OK (data)
6.0 Detailed Component Design
6.1 Access Token (JWT)
Type: Signed JWT (JWS).
Signing Algorithm: HS256 (HMAC using SHA-256). The secret key must be a long, high-entropy string stored securely as an environment variable and never exposed to the client.
Enterprise Recommendation: For higher security environments, consider RS256 (RSA). This uses a public/private key pair, allowing other internal services to validate the token with only the public key, without being able to sign new ones. For this project, HS256 is a sufficient and simpler starting point.
Lifetime: 15 minutes. This is a short but reasonable duration that limits the impact of a token leak.
Payload (Claims): The JWT payload will be kept minimal to reduce token size.
JSON

{
  "iss": "https://api.skyfinetworks.com", // Issuer
  "aud": "https://app.skyfinetworks.com", // Audience
  "iat": 1667884800,                      // Issued At
  "nbf": 1667884800,                      // Not Before
  "exp": 1667885700,                      // Expiration Time (iat + 15 mins)
  "sub": "123",                           // Subject (User ID)
  "rol": ["support_agent", "finance_clerk"] // Custom claim for user roles
}
Security Note: The rol (roles) claim is a performance optimization for the RBAC system. The signature prevents the client from tampering with their roles.
6.2 Refresh Token
Format: A cryptographically secure random string of at least 64 characters. It is not a JWT. It is an opaque identifier.
Storage (Server): Stored in a dedicated refresh_tokens database table. The token itself must be hashed in the database (e.g., with SHA256) to prevent session hijacking even if the database is compromised.
Storage (Client): Stored in a secure, httpOnly, SameSite=Strict cookie.
httpOnly: Prevents the token from being accessed by JavaScript, mitigating XSS attacks.
secure: Ensures the cookie is only sent over HTTPS.
SameSite=Strict: Prevents the browser from sending the cookie with cross-site requests, mitigating CSRF attacks.
Lifetime: 30 days. This allows for a "remember me" functionality.
Rotation: When a refresh token is used, it should be invalidated and a new one issued. This is known as Refresh Token Rotation. It helps detect token theft; if a stolen token is used, the legitimate user's subsequent attempt will fail, and the system can invalidate the entire family of tokens and force a logout.
6.3 refresh_tokens Table Schema
Column Name	Data Type	Description
id	BIGINT	PK.
user_id	BIGINT	FK to users.id. The user this token belongs to.
token_hash	VARCHAR(255)	The SHA256 hash of the refresh token. Indexed.
user_agent	TEXT	The User-Agent of the client that requested the token.
ip_address	VARCHAR(45)	The IP address of the client.
expires_at	TIMESTAMP	The expiry date of this token.
created_at	TIMESTAMP	Record creation timestamp.
Security Note: Storing user_agent and ip_address allows for additional security checks. When a refresh token is used, the server can verify that the request is coming from the same client profile, making it harder for a stolen token to be used.

7.0 API Endpoints
POST /api/v1/auth/login

Request Body: { "email": "...", "password": "..." }
Action: Validates credentials. On success, returns an Access Token and sets the Refresh Token cookie.
Response: 200 OK with { "accessToken": "..." }
POST /api/v1/auth/refresh

Request Body: (Empty). The refresh token is sent in the httpOnly cookie.
Action: Validates the refresh token. On success, returns a new Access Token (and rotates the refresh token).
Response: 200 OK with { "accessToken": "..." }
POST /api/v1/auth/logout

Request Body: (Empty).
Action: Invalidates the refresh token (by deleting it from the database). This effectively logs the user out on the backend. The client should then clear its Access Token from memory.
Response: 204 No Content
8.0 Frontend Implementation Strategy
API Client (e.g., Axios): An API client instance will be configured with an interceptor.
Request Interceptor: Automatically adds the Authorization: Bearer <accessToken> header to every outgoing request.
Response Interceptor: If an API call returns a 401 Unauthorized with an error indicating an expired token, the interceptor will:
"Pause" the original request.
Silently call the /auth/refresh endpoint.
On success, update the Access Token in memory.
Re-run the original, failed request with the new token.
If the refresh fails, it will redirect the user to the login page.
State Management: The Access Token and user information will be stored in the Redux store. The Refresh Token is handled automatically by the browser and is not accessible to the application's JavaScript code.
9.0 Risks
Risk	Description	Mitigation Strategy
Cross-Site Scripting (XSS)	An attacker injects malicious JS into the site to steal the Access Token.	The Access Token is stored in JS memory, making it vulnerable. A strong Content Security Policy (CSP) is the primary defense against XSS. Storing the Refresh Token in an httpOnly cookie protects it.
Cross-Site Request Forgery (CSRF)	An attacker tricks a user into making a malicious request from another site.	Using SameSite=Strict on the Refresh Token cookie provides strong protection. For older browsers, traditional CSRF tokens can be implemented as a fallback.
Token Secret Leakage	The JWT secret key is exposed.	The secret must only be stored as an environment variable, managed by a secure secret management system (e.g., AWS Secrets Manager, HashiCorp Vault), and never committed to version control. Regular key rotation is recommended.
Refresh Token Theft	An attacker steals the Refresh Token from the user's computer.	Refresh Token Rotation helps detect this. Storing the token hash in the DB prevents session hijacking from a DB leak. Additional checks against IP/User-Agent can add another layer of defense.
