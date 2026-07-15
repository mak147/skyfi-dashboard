# Phase 1 Security Review

## Controls observed

JWT access tokens, rotating opaque refresh tokens, HttpOnly/SameSite cookies, prepared PDO statements, RBAC middleware, CORS allowlists, trace IDs, structured logging, rate limiting, credential encryption, and production security headers are present.

## Findings and actions

1. **Critical — password reset token disclosure (fixed).** `forgot-password` returned a reset token in JSON. Responses now disclose only `{requested: true}` unless `EXPOSE_PASSWORD_RESET_TOKEN=true` is explicitly set. The option defaults false and must never be enabled in shared environments.
2. **High — secret/config startup validation.** Production startup should fail when JWT/encryption secrets are empty, placeholders, or short. Schedule before deployment packaging.
3. **High — authorization consistency.** Inventory route/controller permission checks and run a deny-by-default endpoint matrix.
4. **High — rate limiting.** Database rate limiting protects selected authentication actions; extend policy to refresh and sensitive exports after load testing.
5. **Medium — CORS/cookies.** Production requires HTTPS, `REFRESH_COOKIE_SECURE=true`, exact origins, and trusted proxy configuration.
6. **Medium — broad exception catches.** Integration fallbacks should log trace context and distinguish expected remote failures from programming defects.
7. **Medium — file/export surfaces.** Enforce MIME, size, storage isolation, filename sanitization, and spreadsheet formula escaping across imports/exports.

## Production gate

No debug mode; reset-token exposure false; strong rotated secrets; TLS; least-privilege DB user; writable logs outside web root; reviewed CORS; dependency audit; RBAC matrix test; backup restore test.
