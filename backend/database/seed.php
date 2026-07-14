<?php

declare(strict_types=1);

use SkyFi\Database\Seeders\AuthSeeder;
use SkyFi\Shared\Config\Environment;

require dirname(__DIR__) . '/autoload.php';
require __DIR__ . '/seeders/PermissionCatalog.php';
require __DIR__ . '/seeders/AuthSeeder.php';
Environment::load(dirname(__DIR__) . '/.env');
$database = require dirname(__DIR__) . '/config/database.php';
$connection = new PDO(
    (string) $database['dsn'],
    (string) $database['username'],
    (string) $database['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
);

(new AuthSeeder())->run(
    $connection,
    getenv('SEED_ADMIN_EMAIL') ?: null,
    getenv('SEED_ADMIN_PASSWORD') ?: null,
);

echo "Authentication roles and permissions seeded." . PHP_EOL;
