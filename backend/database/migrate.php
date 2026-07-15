<?php

declare(strict_types=1);

/**
 * Migration runner entry point.
 *
 * Usage:
 *   php database/migrate.php
 *
 * Options:
 *   --pretend   Only show which migrations would be applied, do not apply.
 */

use SkyFi\Database\Migrator;
use SkyFi\Shared\Config\Environment;

require dirname(__DIR__) . '/autoload.php';

Environment::load(dirname(__DIR__) . '/.env');

$database = require dirname(__DIR__) . '/config/database.php';
$pretend = in_array('--pretend', $argv ?? [], true);

$pdo = new PDO(
    (string) $database['dsn'],
    (string) $database['username'],
    (string) $database['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
);

$migrator = new Migrator($pdo, __DIR__ . '/migrations');

echo 'SkyFi Database Migrator' . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

if ($pretend) {
    $all = glob(__DIR__ . '/migrations/*.sql');
    sort($all);
    $applied = $migrator->getApplied();
    echo 'Pending migrations:' . PHP_EOL;
    foreach ($all as $file) {
        $filename = basename($file);
        if (!in_array($filename, $applied, true)) {
            echo '  ▶ ' . $filename . PHP_EOL;
        }
    }
    echo PHP_EOL . 'Run without --pretend to apply.' . PHP_EOL;
    exit(0);
}

$result = $migrator->migrate();

echo PHP_EOL . 'Applied:   ' . count($result['applied']) . PHP_EOL;
foreach ($result['applied'] as $name) {
    echo '  ✓ ' . $name . PHP_EOL;
}

echo 'Skipped:   ' . count($result['skipped']) . PHP_EOL;

if ($result['errors'] !== []) {
    echo 'Errors:    ' . count($result['errors']) . PHP_EOL;
    foreach ($result['errors'] as $name => $error) {
        echo '  ✗ ' . $name . ': ' . $error . PHP_EOL;
    }
    exit(1);
}

echo PHP_EOL . 'Migration complete.' . PHP_EOL;
