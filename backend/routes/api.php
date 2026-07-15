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

    $registerPppoeRoutes = require dirname(__DIR__) . '/src/Pppoe/Routes/pppoe.php';
    $registerPppoeRoutes($router, $container);

    $registerHotspotRoutes = require dirname(__DIR__) . '/src/Hotspot/Routes/hotspot.php';
    $registerHotspotRoutes($router, $container);

    $registerInfrastructureRoutes = require dirname(__DIR__) . '/src/Infrastructure/Routes/infrastructure.php';
    $registerInfrastructureRoutes($router, $container);

    $registerMonitoringRoutes = require dirname(__DIR__) . '/src/Monitoring/Routes/monitoring.php';
    $registerMonitoringRoutes($router, $container);

    $registerSupportRoutes = require dirname(__DIR__) . '/src/Support/Routes/support.php';
    $registerSupportRoutes($router, $container);

    $registerInventoryRoutes = require dirname(__DIR__) . '/src/Inventory/Routes/inventory.php';
    $registerInventoryRoutes($router, $container);

    $registerPurchasingRoutes = require dirname(__DIR__) . '/src/Purchasing/Routes/purchasing.php';
    $registerPurchasingRoutes($router, $container);

    $registerVendorRoutes = require dirname(__DIR__) . '/src/Vendors/Routes/vendors.php';
    $registerVendorRoutes($router, $container);
};
