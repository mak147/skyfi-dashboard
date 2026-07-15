<?php

declare(strict_types=1);

return [
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'url' => getenv('APP_URL') ?: 'http://localhost:8080',
    'issuer' => getenv('APP_ISSUER') ?: 'https://api.skyfinetworks.com',
    'audience' => getenv('APP_AUDIENCE') ?: 'https://app.skyfinetworks.com',
    'jwt_secret' => getenv('JWT_SECRET') ?: '',
    'jwt_access_ttl' => (int) (getenv('JWT_ACCESS_TTL') ?: 900),
    'jwt_refresh_ttl' => (int) (getenv('JWT_REFRESH_TTL') ?: 2592000),
    'jwt_session_refresh_ttl' => (int) (getenv('JWT_SESSION_REFRESH_TTL') ?: 28800),
    'refresh_cookie_name' => getenv('REFRESH_COOKIE_NAME') ?: 'skyfi_refresh_token',
    'refresh_cookie_path' => getenv('REFRESH_COOKIE_PATH') ?: '/api/v1/auth',
    'refresh_cookie_secure' => filter_var(getenv('REFRESH_COOKIE_SECURE') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    // Never expose reset credentials by default; local teams may opt in explicitly.
    'expose_password_reset_token' => filter_var(getenv('EXPOSE_PASSWORD_RESET_TOKEN') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'mikrotik' => require __DIR__ . '/mikrotik.php',
];
