<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store');

$startedAt = microtime(true);
$checks = [];
$ready = true;

$databaseDsn = getenv('DB_DSN') ?: '';
if ($databaseDsn !== '') {
    try {
        $pdo = new PDO(
            $databaseDsn,
            getenv('DB_USERNAME') ?: '',
            getenv('DB_PASSWORD') ?: '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
        );
        $pdo->query('SELECT 1');
        $checks['database'] = ['status' => 'ok'];
    } catch (Throwable) {
        $ready = false;
        $checks['database'] = ['status' => 'fail', 'detail' => 'database check failed'];
    }
} else {
    $ready = false;
    $checks['database'] = ['status' => 'fail', 'detail' => 'DB_DSN is not configured'];
}

$storagePath = dirname(__DIR__) . '/storage/logs';
if (is_dir($storagePath) && is_writable($storagePath)) {
    $checks['storage'] = ['status' => 'ok'];
} else {
    $ready = false;
    $checks['storage'] = ['status' => 'fail', 'detail' => 'storage/logs is not writable'];
}

http_response_code($ready ? 200 : 503);

echo json_encode([
    'status' => $ready ? 'ready' : 'not_ready',
    'service' => 'skyfi-api',
    'version' => '1.0.0',
    'checks' => $checks,
    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
    'timestamp' => gmdate(DATE_ATOM),
], JSON_THROW_ON_ERROR) . PHP_EOL;
