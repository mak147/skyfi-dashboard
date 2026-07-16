<?php

declare(strict_types=1);

use SkyFi\Shared\Config\Environment;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Http\Middleware\SecurityHeadersMiddleware;
use SkyFi\Shared\Http\Middleware\TraceIdMiddleware;
use SkyFi\Shared\Logging\JsonLogger;
use SkyFi\Shared\Providers\Container;

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
require is_file($composerAutoload) ? $composerAutoload : dirname(__DIR__) . '/autoload.php';

Environment::load(dirname(__DIR__) . '/.env');
$config = require dirname(__DIR__) . '/config/app.php';
$databaseConfig = require dirname(__DIR__) . '/config/database.php';
$corsConfig = require dirname(__DIR__) . '/config/cors.php';
$request = Request::fromGlobals();
$traceId = (new TraceIdMiddleware())->traceId($request);

$origin = $request->header('Origin');
if ($origin !== null && in_array($origin, $corsConfig['allowed_origins'], true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: ' . implode(', ', $corsConfig['allowed_headers']));
header('Access-Control-Allow-Methods: ' . implode(', ', $corsConfig['allowed_methods']));
header('X-Trace-Id: ' . $traceId);

if ($request->method() === 'OPTIONS') {
    (new Response(204))->send();
    exit;
}

$logger = new JsonLogger(
    dirname(__DIR__) . '/' . (getenv('LOG_PATH') ?: 'storage/logs/app.log'),
);

try {
    $container = new Container($config, $databaseConfig);
    $router = $container->get(Router::class);
    $registerRoutes = require dirname(__DIR__) . '/routes/api.php';
    $registerRoutes($router, $container);
    $response = $router->dispatch($request)->withHeaders(['X-Trace-Id' => $traceId]);
    $response = SecurityHeadersMiddleware::apply($response, $config['env'] === 'production');
    $response->send();
} catch (Throwable $exception) {
    $logger->exception($exception, $traceId, [
        'request' => [
            'method' => $request->method(),
            'url' => $request->path(),
            'ip_address' => $request->ipAddress(),
        ],
    ]);
    $errorResponse = ApiResponse::error($exception, $traceId, (bool) $config['debug'])
        ->withHeaders(['X-Trace-Id' => $traceId]);
    SecurityHeadersMiddleware::apply($errorResponse, $config['env'] === 'production')->send();
}
