<?php

declare(strict_types=1);

$origins = getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:5173';

return [
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', $origins)))),
    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Trace-Id'],
    'allow_credentials' => true,
];
