<?php

declare(strict_types=1);

use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
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
    $protect = static fn(callable $handler): callable => static function (Request $request) use ($auth, $handler) {
        $attributes = $request->attributes();
        $attributes['claims'] = $auth->authenticate($request);
        return $handler($request->withAttributes($attributes));
    };

    // Static collection paths must be registered before /vendors/{id}.
    $router->add('GET', '/api/v1/vendors/dashboard', $protect($dashboard->show(...)));
    $router->add('GET', '/api/v1/vendors/categories', $protect($categories->index(...)));
    $router->add('POST', '/api/v1/vendors/categories', $protect($categories->store(...)));
    $router->add('PUT', '/api/v1/vendors/categories/{categoryId}', $protect($categories->update(...)));
    $router->add('DELETE', '/api/v1/vendors/categories/{categoryId}', $protect($categories->destroy(...)));
    $router->add('GET', '/api/v1/vendors/contacts', $protect($contacts->index(...)));
    $router->add('GET', '/api/v1/vendors/contracts', $protect($contracts->index(...)));
    $router->add('GET', '/api/v1/vendors/quotations/comparison', $protect($quotations->comparison(...)));
    $router->add('GET', '/api/v1/vendors/quotations', $protect($quotations->index(...)));

    $router->add('GET', '/api/v1/vendors', $protect($suppliers->index(...)));
    $router->add('POST', '/api/v1/vendors', $protect($suppliers->store(...)));
    $router->add('GET', '/api/v1/vendors/{id}', $protect($suppliers->show(...)));
    $router->add('PUT', '/api/v1/vendors/{id}', $protect($suppliers->update(...)));
    $router->add('DELETE', '/api/v1/vendors/{id}', $protect($suppliers->destroy(...)));
    $router->add('PATCH', '/api/v1/vendors/{id}/activate', $protect($suppliers->activate(...)));
    $router->add('PATCH', '/api/v1/vendors/{id}/status', $protect($suppliers->status(...)));

    $router->add('GET', '/api/v1/vendors/{id}/contacts', $protect($contacts->supplierIndex(...)));
    $router->add('POST', '/api/v1/vendors/{id}/contacts', $protect($contacts->store(...)));
    $router->add('GET', '/api/v1/vendors/{id}/contacts/{contactId}', $protect($contacts->show(...)));
    $router->add('PUT', '/api/v1/vendors/{id}/contacts/{contactId}', $protect($contacts->update(...)));
    $router->add('DELETE', '/api/v1/vendors/{id}/contacts/{contactId}', $protect($contacts->destroy(...)));
    $router->add('PATCH', '/api/v1/vendors/{id}/contacts/{contactId}/primary', $protect($contacts->primary(...)));
    $router->add('PATCH', '/api/v1/vendors/{id}/contacts/{contactId}/emergency', $protect($contacts->emergency(...)));

    $router->add('GET', '/api/v1/vendors/{id}/contracts', $protect($contracts->supplierIndex(...)));
    $router->add('POST', '/api/v1/vendors/{id}/contracts', $protect($contracts->store(...)));
    $router->add('GET', '/api/v1/vendors/{id}/contracts/{contractId}', $protect($contracts->show(...)));
    $router->add('PUT', '/api/v1/vendors/{id}/contracts/{contractId}', $protect($contracts->update(...)));
    $router->add('DELETE', '/api/v1/vendors/{id}/contracts/{contractId}', $protect($contracts->destroy(...)));

    $router->add('GET', '/api/v1/vendors/{id}/quotations', $protect($quotations->supplierIndex(...)));
    $router->add('POST', '/api/v1/vendors/{id}/quotations', $protect($quotations->store(...)));
    $router->add('GET', '/api/v1/vendors/{id}/quotations/{quotationId}', $protect($quotations->show(...)));
    $router->add('PUT', '/api/v1/vendors/{id}/quotations/{quotationId}', $protect($quotations->update(...)));
    $router->add('DELETE', '/api/v1/vendors/{id}/quotations/{quotationId}', $protect($quotations->destroy(...)));
    $router->add('GET', '/api/v1/vendors/{id}/quotations/{quotationId}/history', $protect($quotations->history(...)));

    $router->add('GET', '/api/v1/vendors/{id}/performance', $protect($performance->performance(...)));
    $router->add('GET', '/api/v1/vendors/{id}/ratings', $protect($performance->ratings(...)));
    $router->add('POST', '/api/v1/vendors/{id}/ratings', $protect($performance->store(...)));
    $router->add('PUT', '/api/v1/vendors/{id}/ratings/{ratingId}', $protect($performance->update(...)));
    $router->add('DELETE', '/api/v1/vendors/{id}/ratings/{ratingId}', $protect($performance->destroy(...)));

    $router->add('GET', '/api/v1/vendors/{id}/purchase-orders', $protect($suppliers->purchaseOrders(...)));
    $router->add('GET', '/api/v1/vendors/{id}/products', $protect($suppliers->products(...)));
    $router->add('GET', '/api/v1/vendors/{id}/financial-references', $protect($suppliers->financialReferences(...)));
};
