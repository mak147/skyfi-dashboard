<?php

declare(strict_types=1);

use SkyFi\Finance\Controllers\FinanceController;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $controller = $container->get(FinanceController::class);
    $auth = $container->get(JwtAuthMiddleware::class);
    $permissions = $container->get(RequirePermissionMiddleware::class);
    $protect = static fn(string $permission, callable $handler): callable => static function (Request $request) use ($auth, $permissions, $permission, $handler): Response {
        $claims = $auth->authenticate($request);
        $permissions->authorize((int) ($claims['sub'] ?? 0), $permission);
        $attributes = $request->attributes();
        $attributes['claims'] = $claims;
        return $handler($request->withAttributes($attributes));
    };
    $actor = static fn(Request $request): int => (int) ($request->attributes()['claims']['sub'] ?? 0);

    $router->add('GET', '/api/v1/finance/dashboard', $protect('finance.view', static fn(): Response => new Response(200, ['data' => $controller->dashboard()])));
    $router->add('GET', '/api/v1/finance/chart-of-accounts', $protect('finance.view', static fn(): Response => new Response(200, ['data' => $controller->getChartOfAccounts()])));
    $router->add('POST', '/api/v1/finance/chart-of-accounts', $protect('finance.manage', static fn(Request $request): Response => new Response(201, ['data' => $controller->createChartOfAccount($request->body())])));
    $router->add('GET', '/api/v1/finance/accounts', $protect('finance.view', static fn(): Response => new Response(200, ['data' => $controller->getFinancialAccounts()])));
    $router->add('POST', '/api/v1/finance/accounts', $protect('accounts.manage', static fn(Request $request): Response => new Response(201, ['data' => $controller->createFinancialAccount($request->body())])));
    $router->add('GET', '/api/v1/finance/ledger', $protect('finance.view', static fn(): Response => new Response(200, ['data' => $controller->getLedger()])));
    $router->add('GET', '/api/v1/finance/journal-entries', $protect('finance.view', static fn(): Response => new Response(200, ['data' => $controller->getJournalEntries()])));
    $router->add('POST', '/api/v1/finance/journal-entries', $protect('finance.create', static fn(Request $request): Response => new Response(201, ['data' => $controller->createJournalEntry($request->body(), $actor($request))])));
    $router->add('GET', '/api/v1/finance/expenses', $protect('finance.view', static fn(): Response => new Response(200, ['data' => $controller->getExpenses()])));
    $router->add('POST', '/api/v1/finance/expenses', $protect('expenses.manage', static fn(Request $request): Response => new Response(201, ['data' => $controller->createExpense($request->body(), $actor($request))])));
    $router->add('GET', '/api/v1/finance/revenue', $protect('finance.view', static fn(): Response => new Response(200, ['data' => $controller->getRevenues()])));
    $router->add('POST', '/api/v1/finance/revenue', $protect('revenue.manage', static fn(Request $request): Response => new Response(201, ['data' => $controller->createRevenue($request->body(), $actor($request))])));
};
