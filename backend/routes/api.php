<?php

declare(strict_types=1);

use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $registerAuthRoutes = require __DIR__ . '/auth.php';
    $registerAuthRoutes($router, $container->get(\SkyFi\Shared\Auth\Controllers\AuthController::class));

    $registerRbacRoutes = require __DIR__ . '/rbac.php';
    $registerRbacRoutes($router, $container);

    $registerDashboardRoutes = require __DIR__ . '/dashboard.php';
    $registerDashboardRoutes($router, $container);

    $registerCustomerRoutes = require dirname(__DIR__) . '/src/Customers/Routes/customers.php';
    $registerCustomerRoutes($router, $container);

    $registerPackageRoutes = require dirname(__DIR__) . '/src/Packages/Routes/packages.php';
    $registerPackageRoutes($router, $container);

    $registerConnectionRoutes = require dirname(__DIR__) . '/src/Connections/Routes/connections.php';
    $registerConnectionRoutes($router, $container);

    $registerBillingRoutes = require dirname(__DIR__) . '/src/Billing/Routes/billing.php';
    $registerBillingRoutes($router, $container);

    $registerPaymentRoutes = require dirname(__DIR__) . '/src/Payments/Routes/payments.php';
    $registerPaymentRoutes($router, $container);

    $registerFinanceRoutes = require dirname(__DIR__) . '/src/Finance/Routes/finance.php';
    $registerFinanceRoutes($router, $container);

    $registerMikrotikRoutes = require dirname(__DIR__) . '/src/Mikrotik/Routes/mikrotik.php';
    $registerMikrotikRoutes($router, $container);
};
