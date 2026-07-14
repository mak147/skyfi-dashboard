<?php

declare(strict_types=1);

return [
    'dsn' => getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=skyfi;charset=utf8mb4',
    'username' => getenv('DB_USERNAME') ?: 'skyfi',
    'password' => getenv('DB_PASSWORD') ?: '',
];
