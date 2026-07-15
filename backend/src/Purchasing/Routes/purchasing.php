<?php

declare(strict_types=1);

use SkyFi\Purchasing\Controllers\GoodsReceiptController;
use SkyFi\Purchasing\Controllers\PurchaseOrderController;
use SkyFi\Purchasing\Controllers\PurchaseRequestController;
use SkyFi\Purchasing\Controllers\PurchasingDashboardController;
use SkyFi\Purchasing\Controllers\SupplierInvoiceController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
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

    $protect = static fn(callable $handler): callable => static function (Request $request) use ($auth, $handler) {
        $attributes = $request->attributes();
        $attributes['claims'] = $auth->authenticate($request);
        return $handler($request->withAttributes($attributes));
    };

    // Dashboard
    $router->add('GET', '/api/v1/purchasing/dashboard', $protect($dashboard->dashboard(...)));
    $router->add('GET', '/api/v1/purchasing/finance-postings', $protect($dashboard->financePostings(...)));

    // Purchase Requests
    $router->add('GET', '/api/v1/purchasing/requests', $protect($requests->index(...)));
    $router->add('POST', '/api/v1/purchasing/requests', $protect($requests->store(...)));
    $router->add('GET', '/api/v1/purchasing/requests/{id}', $protect($requests->show(...)));
    $router->add('PUT', '/api/v1/purchasing/requests/{id}', $protect($requests->update(...)));
    $router->add('POST', '/api/v1/purchasing/requests/{id}/submit', $protect($requests->submit(...)));
    $router->add('POST', '/api/v1/purchasing/requests/{id}/approve', $protect($requests->approve(...)));
    $router->add('POST', '/api/v1/purchasing/requests/{id}/reject', $protect($requests->reject(...)));
    $router->add('POST', '/api/v1/purchasing/requests/{id}/cancel', $protect($requests->cancel(...)));

    // Purchase Orders
    $router->add('GET', '/api/v1/purchasing/orders', $protect($orders->index(...)));
    $router->add('POST', '/api/v1/purchasing/orders', $protect($orders->store(...)));
    $router->add('GET', '/api/v1/purchasing/orders/{id}', $protect($orders->show(...)));
    $router->add('PUT', '/api/v1/purchasing/orders/{id}', $protect($orders->update(...)));
    $router->add('POST', '/api/v1/purchasing/orders/{id}/submit', $protect($orders->submit(...)));
    $router->add('POST', '/api/v1/purchasing/orders/{id}/approve', $protect($orders->approve(...)));
    $router->add('POST', '/api/v1/purchasing/orders/{id}/reject', $protect($orders->reject(...)));
    $router->add('POST', '/api/v1/purchasing/orders/{id}/cancel', $protect($orders->cancel(...)));
    $router->add('POST', '/api/v1/purchasing/orders/{id}/close', $protect($orders->close(...)));

    // Goods Receipts
    $router->add('GET', '/api/v1/purchasing/goods-receipts', $protect($receipts->index(...)));
    $router->add('POST', '/api/v1/purchasing/goods-receipts', $protect($receipts->store(...)));
    $router->add('GET', '/api/v1/purchasing/goods-receipts/{id}', $protect($receipts->show(...)));
    $router->add('POST', '/api/v1/purchasing/goods-receipts/{id}/return', $protect($receipts->returnToSupplier(...)));

    // Supplier Invoices
    $router->add('GET', '/api/v1/purchasing/supplier-invoices', $protect($invoices->index(...)));
    $router->add('POST', '/api/v1/purchasing/supplier-invoices', $protect($invoices->store(...)));
    $router->add('GET', '/api/v1/purchasing/supplier-invoices/{id}', $protect($invoices->show(...)));
    $router->add('PUT', '/api/v1/purchasing/supplier-invoices/{id}', $protect($invoices->update(...)));
};
