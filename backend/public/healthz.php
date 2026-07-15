<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store');

echo json_encode([
    'status' => 'ok',
    'service' => 'skyfi-api',
    'timestamp' => gmdate(DATE_ATOM),
], JSON_THROW_ON_ERROR) . PHP_EOL;
