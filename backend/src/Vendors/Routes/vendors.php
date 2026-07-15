<?php

declare(strict_types=1);

use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;
use SkyFi\Vendors\Controllers\SupplierCategoryController;
use SkyFi\Vendors\Controllers\SupplierContactController;
use SkyFi\Vendors\Controllers\SupplierContractController;
use SkyFi\Vendors\Controllers\SupplierController;
use SkyFi\Vendors\Controllers\SupplierPerformanceController;
use SkyFi\Vendors\Controllers\SupplierQuotationController;
use SkyFi\Vendors\Controllers\VendorDashboardController;

return static function (Router $router, Container $container): void {
    $suppliers = $container->get(SupplierController::class);
    $categories = $container->get(SupplierCategoryController::class);
    $contacts = $container->get(SupplierContactController::class);
    $contracts = $container->get(SupplierContractController::class);
    $quotations = $container->get(SupplierQuotationController::class);
    $performance = $container->get(SupplierPerformanceController::class);
    $dashboard = $container->get(VendorDashboardController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    // Static collection paths must be registered before /vendors/{id}.
    $router->add('GET', '/api/v1/vendors/dashboard', ProtectRoute::wrap($auth, $dashboard->show(...)));
    $router->add('GET', '/api/v1/vendors/categories', ProtectRoute::wrap($auth, $categories->index(...)));
    $router->add('POST', '/api/v1/vendors/categories', ProtectRoute::wrap($auth, $categories->store(...)));
    $router->add('PUT', '/api/v1/vendors/categories/{categoryId}', ProtectRoute::wrap($auth, $categories->update(...)));
    $router->add('DELETE', '/api/v1/vendors/categories/{categoryId}', ProtectRoute::wrap($auth, $categories->destroy(...)));
    $router->add('GET', '/api/v1/vendors/contacts', ProtectRoute::wrap($auth, $contacts->index(...)));
    $router->add('GET', '/api/v1/vendors/contracts', ProtectRoute::wrap($auth, $contracts->index(...)));
    $router->add('GET', '/api/v1/vendors/quotations/comparison', ProtectRoute::wrap($auth, $quotations->comparison(...)));
    $router->add('GET', '/api/v1/vendors/quotations', ProtectRoute::wrap($auth, $quotations->index(...)));

    $router->add('GET', '/api/v1/vendors', ProtectRoute::wrap($auth, $suppliers->index(...)));
    $router->add('POST', '/api/v1/vendors', ProtectRoute::wrap($auth, $suppliers->store(...)));
    $router->add('GET', '/api/v1/vendors/{id}', ProtectRoute::wrap($auth, $suppliers->show(...)));
    $router->add('PUT', '/api/v1/vendors/{id}', ProtectRoute::wrap($auth, $suppliers->update(...)));
    $router->add('DELETE', '/api/v1/vendors/{id}', ProtectRoute::wrap($auth, $suppliers->destroy(...)));
    $router->add('PATCH', '/api/v1/vendors/{id}/activate', ProtectRoute::wrap($auth, $suppliers->activate(...)));
    $router->add('PATCH', '/api/v1/vendors/{id}/status', ProtectRoute::wrap($auth, $suppliers->status(...)));

    $router->add('GET', '/api/v1/vendors/{id}/contacts', ProtectRoute::wrap($auth, $contacts->supplierIndex(...)));
    $router->add('POST', '/api/v1/vendors/{id}/contacts', ProtectRoute::wrap($auth, $contacts->store(...)));
    $router->add('GET', '/api/v1/vendors/{id}/contacts/{contactId}', ProtectRoute::wrap($auth, $contacts->show(...)));
    $router->add('PUT', '/api/v1/vendors/{id}/contacts/{contactId}', ProtectRoute::wrap($auth, $contacts->update(...)));
    $router->add('DELETE', '/api/v1/vendors/{id}/contacts/{contactId}', ProtectRoute::wrap($auth, $contacts->destroy(...)));
    $router->add('PATCH', '/api/v1/vendors/{id}/contacts/{contactId}/primary', ProtectRoute::wrap($auth, $contacts->primary(...)));
    $router->add('PATCH', '/api/v1/vendors/{id}/contacts/{contactId}/emergency', ProtectRoute::wrap($auth, $contacts->emergency(...)));

    $router->add('GET', '/api/v1/vendors/{id}/contracts', ProtectRoute::wrap($auth, $contracts->supplierIndex(...)));
    $router->add('POST', '/api/v1/vendors/{id}/contracts', ProtectRoute::wrap($auth, $contracts->store(...)));
    $router->add('GET', '/api/v1/vendors/{id}/contracts/{contractId}', ProtectRoute::wrap($auth, $contracts->show(...)));
    $router->add('PUT', '/api/v1/vendors/{id}/contracts/{contractId}', ProtectRoute::wrap($auth, $contracts->update(...)));
    $router->add('DELETE', '/api/v1/vendors/{id}/contracts/{contractId}', ProtectRoute::wrap($auth, $contracts->destroy(...)));

    $router->add('GET', '/api/v1/vendors/{id}/quotations', ProtectRoute::wrap($auth, $quotations->supplierIndex(...)));
    $router->add('POST', '/api/v1/vendors/{id}/quotations', ProtectRoute::wrap($auth, $quotations->store(...)));
    $router->add('GET', '/api/v1/vendors/{id}/quotations/{quotationId}', ProtectRoute::wrap($auth, $quotations->show(...)));
    $router->add('PUT', '/api/v1/vendors/{id}/quotations/{quotationId}', ProtectRoute::wrap($auth, $quotations->update(...)));
    $router->add('DELETE', '/api/v1/vendors/{id}/quotations/{quotationId}', ProtectRoute::wrap($auth, $quotations->destroy(...)));
    $router->add('GET', '/api/v1/vendors/{id}/quotations/{quotationId}/history', ProtectRoute::wrap($auth, $quotations->history(...)));

    $router->add('GET', '/api/v1/vendors/{id}/performance', ProtectRoute::wrap($auth, $performance->performance(...)));
    $router->add('GET', '/api/v1/vendors/{id}/ratings', ProtectRoute::wrap($auth, $performance->ratings(...)));
    $router->add('POST', '/api/v1/vendors/{id}/ratings', ProtectRoute::wrap($auth, $performance->store(...)));
    $router->add('PUT', '/api/v1/vendors/{id}/ratings/{ratingId}', ProtectRoute::wrap($auth, $performance->update(...)));
    $router->add('DELETE', '/api/v1/vendors/{id}/ratings/{ratingId}', ProtectRoute::wrap($auth, $performance->destroy(...)));

    $router->add('GET', '/api/v1/vendors/{id}/purchase-orders', ProtectRoute::wrap($auth, $suppliers->purchaseOrders(...)));
    $router->add('GET', '/api/v1/vendors/{id}/products', ProtectRoute::wrap($auth, $suppliers->products(...)));
    $router->add('GET', '/api/v1/vendors/{id}/financial-references', ProtectRoute::wrap($auth, $suppliers->financialReferences(...)));
};
