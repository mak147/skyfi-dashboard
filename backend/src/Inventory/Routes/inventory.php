<?php

declare(strict_types=1);

use SkyFi\Inventory\Controllers\AssetController;
use SkyFi\Inventory\Controllers\CatalogController;
use SkyFi\Inventory\Controllers\InventoryLookupController;
use SkyFi\Inventory\Controllers\ProductController;
use SkyFi\Inventory\Controllers\StockController;
use SkyFi\Inventory\Controllers\TransferController;
use SkyFi\Inventory\Controllers\WarehouseController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $products = $container->get(ProductController::class);
    $catalog = $container->get(CatalogController::class);
    $warehouses = $container->get(WarehouseController::class);
    $assets = $container->get(AssetController::class);
    $stock = $container->get(StockController::class);
    $transfers = $container->get(TransferController::class);
    $lookups = $container->get(InventoryLookupController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $protect = static fn(callable $handler): callable => static function (Request $request) use ($auth, $handler) {
        $attributes = $request->attributes();
        $attributes['claims'] = $auth->authenticate($request);
        return $handler($request->withAttributes($attributes));
    };
    $withAttribute = static fn(string $key, string $value, callable $handler): callable => static function (Request $request) use ($key, $value, $handler) {
        $attributes = $request->attributes();
        $attributes[$key] = $value;
        return $handler($request->withAttributes($attributes));
    };
    $withResource = static fn(string $resource, callable $handler): callable => static function (Request $request) use ($resource, $handler) {
        $attributes = $request->attributes();
        $attributes['route_params'] = [...($attributes['route_params'] ?? []), 'resource' => $resource];
        return $handler($request->withAttributes($attributes));
    };

    $router->add('GET', '/api/v1/inventory/dashboard', $protect($stock->dashboard(...)));
    $router->add('GET', '/api/v1/inventory/search', $protect($lookups->search(...)));
    $router->add('GET', '/api/v1/inventory/lookups/{resource}', $protect($lookups->lookup(...)));

    $router->add('GET', '/api/v1/inventory/products', $protect($products->index(...)));
    $router->add('POST', '/api/v1/inventory/products', $protect($products->store(...)));
    $router->add('GET', '/api/v1/inventory/products/stock', $protect($products->stock(...)));
    $router->add('GET', '/api/v1/inventory/products/{id}', $protect($products->show(...)));
    $router->add('PUT', '/api/v1/inventory/products/{id}', $protect($products->update(...)));
    $router->add('DELETE', '/api/v1/inventory/products/{id}', $protect($products->destroy(...)));

    foreach (['categories', 'brands', 'models', 'units', 'vendors'] as $resource) {
        $router->add('GET', '/api/v1/inventory/' . $resource, $protect($withResource($resource, $catalog->index(...))));
        $router->add('POST', '/api/v1/inventory/' . $resource, $protect($withResource($resource, $catalog->store(...))));
        $router->add('PUT', '/api/v1/inventory/' . $resource . '/{id}', $protect($withResource($resource, $catalog->update(...))));
        $router->add('DELETE', '/api/v1/inventory/' . $resource . '/{id}', $protect($withResource($resource, $catalog->destroy(...))));
    }

    $router->add('GET', '/api/v1/inventory/warehouses', $protect($warehouses->index(...)));
    $router->add('POST', '/api/v1/inventory/warehouses', $protect($warehouses->store(...)));
    $router->add('GET', '/api/v1/inventory/warehouses/{id}', $protect($warehouses->show(...)));
    $router->add('PUT', '/api/v1/inventory/warehouses/{id}', $protect($warehouses->update(...)));
    $router->add('DELETE', '/api/v1/inventory/warehouses/{id}', $protect($warehouses->destroy(...)));
    $router->add('PATCH', '/api/v1/inventory/warehouses/{id}/status', $protect($warehouses->changeStatus(...)));
    $router->add('GET', '/api/v1/inventory/warehouses/{id}/locations', $protect($warehouses->locations(...)));
    $router->add('POST', '/api/v1/inventory/warehouses/{id}/locations', $protect($warehouses->storeLocation(...)));
    $router->add('PUT', '/api/v1/inventory/warehouses/{id}/locations/{locationId}', $protect($warehouses->updateLocation(...)));
    $router->add('DELETE', '/api/v1/inventory/warehouses/{id}/locations/{locationId}', $protect($warehouses->destroyLocation(...)));

    $router->add('GET', '/api/v1/inventory/assets', $protect($assets->index(...)));
    $router->add('POST', '/api/v1/inventory/assets', $protect($assets->store(...)));
    $router->add('GET', '/api/v1/inventory/assets/{id}', $protect($assets->show(...)));
    $router->add('PUT', '/api/v1/inventory/assets/{id}', $protect($assets->update(...)));
    $router->add('DELETE', '/api/v1/inventory/assets/{id}', $protect($assets->destroy(...)));
    $router->add('PATCH', '/api/v1/inventory/assets/{id}/status', $protect($assets->changeStatus(...)));
    $router->add('POST', '/api/v1/inventory/assets/{id}/assign', $protect($assets->assign(...)));
    $router->add('POST', '/api/v1/inventory/assets/{id}/return', $protect($assets->returnToWarehouse(...)));
    $router->add('GET', '/api/v1/inventory/assets/{id}/timeline', $protect($assets->timeline(...)));

    $router->add('GET', '/api/v1/inventory/stock', $protect($stock->balances(...)));
    $router->add('GET', '/api/v1/inventory/stock-movements', $protect($stock->index(...)));
    $operations = [
        'stock-in' => 'stock_in', 'stock-out' => 'stock_out', 'opening-balance' => 'opening_balance',
        'adjustment-in' => 'adjustment_in', 'adjustment-out' => 'adjustment_out', 'return' => 'return', 'damaged' => 'damaged', 'scrap' => 'scrap',
    ];
    foreach ($operations as $path => $operation) {
        $router->add('POST', '/api/v1/inventory/stock-movements/' . $path, $protect($withAttribute('inventory_operation', $operation, $stock->post(...))));
    }
    $router->add('GET', '/api/v1/inventory/stock-movements/{id}', $protect($stock->show(...)));
    $router->add('POST', '/api/v1/inventory/stock-movements/{id}/reverse', $protect($stock->reverse(...)));

    $router->add('GET', '/api/v1/inventory/transfers', $protect($transfers->index(...)));
    $router->add('POST', '/api/v1/inventory/transfers', $protect($transfers->store(...)));
    $router->add('GET', '/api/v1/inventory/transfers/{id}', $protect($transfers->show(...)));
    $router->add('PUT', '/api/v1/inventory/transfers/{id}', $protect($transfers->update(...)));
    $router->add('DELETE', '/api/v1/inventory/transfers/{id}', $protect($transfers->destroy(...)));
    foreach (['submit', 'approve', 'dispatch', 'receive', 'cancel'] as $action) {
        $router->add('POST', '/api/v1/inventory/transfers/{id}/' . $action, $protect($withAttribute('inventory_transfer_action', $action, $transfers->action(...))));
    }

    $router->add('GET', '/api/v1/inventory/settings/accounting', $protect($stock->accountingSettings(...)));
    $router->add('PUT', '/api/v1/inventory/settings/accounting', $protect($stock->updateAccountingSettings(...)));
    $router->add('GET', '/api/v1/inventory/finance-postings', $protect($stock->financePostings(...)));
    $router->add('POST', '/api/v1/inventory/finance-postings/{id}/retry', $protect($stock->retryFinancePosting(...)));
};
