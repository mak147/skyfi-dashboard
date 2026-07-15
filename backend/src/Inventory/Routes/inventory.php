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
use SkyFi\Shared\Http\Middleware\ProtectRoute;
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

    $router->add('GET', '/api/v1/inventory/dashboard', ProtectRoute::wrap($auth, $stock->dashboard(...)));
    $router->add('GET', '/api/v1/inventory/search', ProtectRoute::wrap($auth, $lookups->search(...)));
    $router->add('GET', '/api/v1/inventory/lookups/{resource}', ProtectRoute::wrap($auth, $lookups->lookup(...)));

    $router->add('GET', '/api/v1/inventory/products', ProtectRoute::wrap($auth, $products->index(...)));
    $router->add('POST', '/api/v1/inventory/products', ProtectRoute::wrap($auth, $products->store(...)));
    $router->add('GET', '/api/v1/inventory/products/stock', ProtectRoute::wrap($auth, $products->stock(...)));
    $router->add('GET', '/api/v1/inventory/products/{id}', ProtectRoute::wrap($auth, $products->show(...)));
    $router->add('PUT', '/api/v1/inventory/products/{id}', ProtectRoute::wrap($auth, $products->update(...)));
    $router->add('DELETE', '/api/v1/inventory/products/{id}', ProtectRoute::wrap($auth, $products->destroy(...)));

    foreach (['categories', 'brands', 'models', 'units', 'vendors'] as $resource) {
        $router->add('GET', '/api/v1/inventory/' . $resource, ProtectRoute::wrap($auth, $withResource($resource, $catalog->index(...))));
        $router->add('POST', '/api/v1/inventory/' . $resource, ProtectRoute::wrap($auth, $withResource($resource, $catalog->store(...))));
        $router->add('PUT', '/api/v1/inventory/' . $resource . '/{id}', ProtectRoute::wrap($auth, $withResource($resource, $catalog->update(...))));
        $router->add('DELETE', '/api/v1/inventory/' . $resource . '/{id}', ProtectRoute::wrap($auth, $withResource($resource, $catalog->destroy(...))));
    }

    $router->add('GET', '/api/v1/inventory/warehouses', ProtectRoute::wrap($auth, $warehouses->index(...)));
    $router->add('POST', '/api/v1/inventory/warehouses', ProtectRoute::wrap($auth, $warehouses->store(...)));
    $router->add('GET', '/api/v1/inventory/warehouses/{id}', ProtectRoute::wrap($auth, $warehouses->show(...)));
    $router->add('PUT', '/api/v1/inventory/warehouses/{id}', ProtectRoute::wrap($auth, $warehouses->update(...)));
    $router->add('DELETE', '/api/v1/inventory/warehouses/{id}', ProtectRoute::wrap($auth, $warehouses->destroy(...)));
    $router->add('PATCH', '/api/v1/inventory/warehouses/{id}/status', ProtectRoute::wrap($auth, $warehouses->changeStatus(...)));
    $router->add('GET', '/api/v1/inventory/warehouses/{id}/locations', ProtectRoute::wrap($auth, $warehouses->locations(...)));
    $router->add('POST', '/api/v1/inventory/warehouses/{id}/locations', ProtectRoute::wrap($auth, $warehouses->storeLocation(...)));
    $router->add('PUT', '/api/v1/inventory/warehouses/{id}/locations/{locationId}', ProtectRoute::wrap($auth, $warehouses->updateLocation(...)));
    $router->add('DELETE', '/api/v1/inventory/warehouses/{id}/locations/{locationId}', ProtectRoute::wrap($auth, $warehouses->destroyLocation(...)));

    $router->add('GET', '/api/v1/inventory/assets', ProtectRoute::wrap($auth, $assets->index(...)));
    $router->add('POST', '/api/v1/inventory/assets', ProtectRoute::wrap($auth, $assets->store(...)));
    $router->add('GET', '/api/v1/inventory/assets/{id}', ProtectRoute::wrap($auth, $assets->show(...)));
    $router->add('PUT', '/api/v1/inventory/assets/{id}', ProtectRoute::wrap($auth, $assets->update(...)));
    $router->add('DELETE', '/api/v1/inventory/assets/{id}', ProtectRoute::wrap($auth, $assets->destroy(...)));
    $router->add('PATCH', '/api/v1/inventory/assets/{id}/status', ProtectRoute::wrap($auth, $assets->changeStatus(...)));
    $router->add('POST', '/api/v1/inventory/assets/{id}/assign', ProtectRoute::wrap($auth, $assets->assign(...)));
    $router->add('POST', '/api/v1/inventory/assets/{id}/return', ProtectRoute::wrap($auth, $assets->returnToWarehouse(...)));
    $router->add('GET', '/api/v1/inventory/assets/{id}/timeline', ProtectRoute::wrap($auth, $assets->timeline(...)));

    $router->add('GET', '/api/v1/inventory/stock', ProtectRoute::wrap($auth, $stock->balances(...)));
    $router->add('GET', '/api/v1/inventory/stock-movements', ProtectRoute::wrap($auth, $stock->index(...)));
    $operations = [
        'stock-in' => 'stock_in', 'stock-out' => 'stock_out', 'opening-balance' => 'opening_balance',
        'adjustment-in' => 'adjustment_in', 'adjustment-out' => 'adjustment_out', 'return' => 'return', 'damaged' => 'damaged', 'scrap' => 'scrap',
    ];
    foreach ($operations as $path => $operation) {
        $router->add('POST', '/api/v1/inventory/stock-movements/' . $path, ProtectRoute::wrap($auth, $withAttribute('inventory_operation', $operation, $stock->post(...))));
    }
    $router->add('GET', '/api/v1/inventory/stock-movements/{id}', ProtectRoute::wrap($auth, $stock->show(...)));
    $router->add('POST', '/api/v1/inventory/stock-movements/{id}/reverse', ProtectRoute::wrap($auth, $stock->reverse(...)));

    $router->add('GET', '/api/v1/inventory/transfers', ProtectRoute::wrap($auth, $transfers->index(...)));
    $router->add('POST', '/api/v1/inventory/transfers', ProtectRoute::wrap($auth, $transfers->store(...)));
    $router->add('GET', '/api/v1/inventory/transfers/{id}', ProtectRoute::wrap($auth, $transfers->show(...)));
    $router->add('PUT', '/api/v1/inventory/transfers/{id}', ProtectRoute::wrap($auth, $transfers->update(...)));
    $router->add('DELETE', '/api/v1/inventory/transfers/{id}', ProtectRoute::wrap($auth, $transfers->destroy(...)));
    foreach (['submit', 'approve', 'dispatch', 'receive', 'cancel'] as $action) {
        $router->add('POST', '/api/v1/inventory/transfers/{id}/' . $action, ProtectRoute::wrap($auth, $withAttribute('inventory_transfer_action', $action, $transfers->action(...))));
    }

    $router->add('GET', '/api/v1/inventory/settings/accounting', ProtectRoute::wrap($auth, $stock->accountingSettings(...)));
    $router->add('PUT', '/api/v1/inventory/settings/accounting', ProtectRoute::wrap($auth, $stock->updateAccountingSettings(...)));
    $router->add('GET', '/api/v1/inventory/finance-postings', ProtectRoute::wrap($auth, $stock->financePostings(...)));
    $router->add('POST', '/api/v1/inventory/finance-postings/{id}/retry', ProtectRoute::wrap($auth, $stock->retryFinancePosting(...)));
};
