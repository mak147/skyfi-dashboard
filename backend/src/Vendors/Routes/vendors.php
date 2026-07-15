<?php

declare(strict_types=1);

use SkyFi\Vendors\Controllers\VendorController;
use SkyFi\Vendors\Controllers\VendorContactController;
use SkyFi\Vendors\Controllers\VendorContractController;
use SkyFi\Vendors\Controllers\VendorQuotationController;
use SkyFi\Vendors\Controllers\VendorRatingController;
use SkyFi\Vendors\Controllers\VendorDashboardController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $dashboard = $container->get(VendorDashboardController::class);
    $vendors = $container->get(VendorController::class);
    $contacts = $container->get(VendorContactController::class);
    $contracts = $container->get(VendorContractController::class);
    $quotations = $container->get(VendorQuotationController::class);
    $ratings = $container->get(VendorRatingController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $protect = static fn(callable $handler): callable => static function (Request $request) use ($auth, $handler) {
        $attributes = $request->attributes();
        $attributes['claims'] = $auth->authenticate($request);
        return $handler($request->withAttributes($attributes));
    };

    // Dashboard
    $router->add('GET', '/api/v1/vendors/dashboard', $protect($dashboard->dashboard(...)));

    // Global Central Contacts, Contracts, and Quotations (for dedicated directory tabs)
    $router->add('GET', '/api/v1/vendors/contacts', $protect($contacts->index(...)));
    $router->add('GET', '/api/v1/vendors/contracts', $protect($contracts->index(...)));
    $router->add('GET', '/api/v1/vendors/quotations', $protect($quotations->index(...)));

    // Individual Contact mutations
    $router->add('PUT', '/api/v1/vendors/contacts/{contactId}', $protect($contacts->update(...)));
    $router->add('DELETE', '/api/v1/vendors/contacts/{contactId}', $protect($contacts->destroy(...)));

    // Individual Contract mutations
    $router->add('PUT', '/api/v1/vendors/contracts/{contractId}', $protect($contracts->update(...)));
    $router->add('DELETE', '/api/v1/vendors/contracts/{contractId}', $protect($contracts->destroy(...)));

    // Individual Quotation mutations
    $router->add('PUT', '/api/v1/vendors/quotations/{quotationId}', $protect($quotations->updateStatus(...)));
    $router->add('DELETE', '/api/v1/vendors/quotations/{quotationId}', $protect($quotations->destroy(...)));

    // Supplier CRUD & actions
    $router->add('GET', '/api/v1/vendors', $protect($vendors->index(...)));
    $router->add('POST', '/api/v1/vendors', $protect($vendors->store(...)));
    $router->add('GET', '/api/v1/vendors/{id}', $protect($vendors->show(...)));
    $router->add('PUT', '/api/v1/vendors/{id}', $protect($vendors->update(...)));
    $router->add('DELETE', '/api/v1/vendors/{id}', $protect($vendors->destroy(...)));
    $router->add('POST', '/api/v1/vendors/{id}/activate', $protect($vendors->activate(...)));
    $router->add('GET', '/api/v1/vendors/{id}/purchasing-history', $protect($vendors->purchasingHistory(...)));

    // Supplier-specific sub-resources
    $router->add('GET', '/api/v1/vendors/{id}/contacts', $protect($contacts->index(...)));
    $router->add('POST', '/api/v1/vendors/{id}/contacts', $protect($contacts->store(...)));

    $router->add('GET', '/api/v1/vendors/{id}/contracts', $protect($contracts->index(...)));
    $router->add('POST', '/api/v1/vendors/{id}/contracts', $protect($contracts->store(...)));

    $router->add('GET', '/api/v1/vendors/{id}/quotations', $protect($quotations->index(...)));
    $router->add('POST', '/api/v1/vendors/{id}/quotations', $protect($quotations->store(...)));

    $router->add('GET', '/api/v1/vendors/{id}/ratings', $protect($ratings->index(...)));
    $router->add('POST', '/api/v1/vendors/{id}/ratings', $protect($ratings->store(...)));
};
