<?php

declare(strict_types=1);

use SkyFi\Purchasing\Controllers\GoodsReceiptController;
use SkyFi\Purchasing\Controllers\PurchaseOrderController;
use SkyFi\Purchasing\Controllers\PurchaseRequestController;
use SkyFi\Purchasing\Controllers\PurchasingDashboardController;
use SkyFi\Purchasing\Controllers\SupplierInvoiceController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $dashboard = $container->get(PurchasingDashboardController::class);
    $requests = $container->get(PurchaseRequestController::class);
    $orders = $container->get(PurchaseOrderController::class);
    $receipts = $container->get(GoodsReceiptController::class);
    $invoices = $container->get(SupplierInvoiceController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    // Dashboard
    $router->add('GET', '/api/v1/purchasing/dashboard', ProtectRoute::wrap($auth, $dashboard->dashboard(...)));
    $router->add('GET', '/api/v1/purchasing/finance-postings', ProtectRoute::wrap($auth, $dashboard->financePostings(...)));

    // Purchase Requests
    $router->add('GET', '/api/v1/purchasing/requests', ProtectRoute::wrap($auth, $requests->index(...)));
    $router->add('POST', '/api/v1/purchasing/requests', ProtectRoute::wrap($auth, $requests->store(...)));
    $router->add('GET', '/api/v1/purchasing/requests/{id}', ProtectRoute::wrap($auth, $requests->show(...)));
    $router->add('PUT', '/api/v1/purchasing/requests/{id}', ProtectRoute::wrap($auth, $requests->update(...)));
    $router->add('POST', '/api/v1/purchasing/requests/{id}/submit', ProtectRoute::wrap($auth, $requests->submit(...)));
    $router->add('POST', '/api/v1/purchasing/requests/{id}/approve', ProtectRoute::wrap($auth, $requests->approve(...)));
    $router->add('POST', '/api/v1/purchasing/requests/{id}/reject', ProtectRoute::wrap($auth, $requests->reject(...)));
    $router->add('POST', '/api/v1/purchasing/requests/{id}/cancel', ProtectRoute::wrap($auth, $requests->cancel(...)));

    // Purchase Orders
    $router->add('GET', '/api/v1/purchasing/orders', ProtectRoute::wrap($auth, $orders->index(...)));
    $router->add('POST', '/api/v1/purchasing/orders', ProtectRoute::wrap($auth, $orders->store(...)));
    $router->add('GET', '/api/v1/purchasing/orders/{id}', ProtectRoute::wrap($auth, $orders->show(...)));
    $router->add('PUT', '/api/v1/purchasing/orders/{id}', ProtectRoute::wrap($auth, $orders->update(...)));
    $router->add('POST', '/api/v1/purchasing/orders/{id}/submit', ProtectRoute::wrap($auth, $orders->submit(...)));
    $router->add('POST', '/api/v1/purchasing/orders/{id}/approve', ProtectRoute::wrap($auth, $orders->approve(...)));
    $router->add('POST', '/api/v1/purchasing/orders/{id}/reject', ProtectRoute::wrap($auth, $orders->reject(...)));
    $router->add('POST', '/api/v1/purchasing/orders/{id}/cancel', ProtectRoute::wrap($auth, $orders->cancel(...)));
    $router->add('POST', '/api/v1/purchasing/orders/{id}/close', ProtectRoute::wrap($auth, $orders->close(...)));

    // Goods Receipts
    $router->add('GET', '/api/v1/purchasing/goods-receipts', ProtectRoute::wrap($auth, $receipts->index(...)));
    $router->add('POST', '/api/v1/purchasing/goods-receipts', ProtectRoute::wrap($auth, $receipts->store(...)));
    $router->add('GET', '/api/v1/purchasing/goods-receipts/{id}', ProtectRoute::wrap($auth, $receipts->show(...)));
    $router->add('POST', '/api/v1/purchasing/goods-receipts/{id}/return', ProtectRoute::wrap($auth, $receipts->returnToSupplier(...)));

    // Supplier Invoices
    $router->add('GET', '/api/v1/purchasing/supplier-invoices', ProtectRoute::wrap($auth, $invoices->index(...)));
    $router->add('POST', '/api/v1/purchasing/supplier-invoices', ProtectRoute::wrap($auth, $invoices->store(...)));
    $router->add('GET', '/api/v1/purchasing/supplier-invoices/{id}', ProtectRoute::wrap($auth, $invoices->show(...)));
    $router->add('PUT', '/api/v1/purchasing/supplier-invoices/{id}', ProtectRoute::wrap($auth, $invoices->update(...)));
};
