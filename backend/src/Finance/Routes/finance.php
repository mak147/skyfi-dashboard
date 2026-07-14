<?php

declare(strict_types=1);

use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;
use SkyFi\Finance\Controllers\FinanceController;
use SkyFi\Shared\Http\Request;

return static function (Router $router, Container $container): void {
    $router->group(['prefix' => '/api/finance', 'middleware' => ['auth']], static function (Router $router) use ($container) {
        
        $router->get('/dashboard', static function (Request $req) use ($container) {
            $req->user()->requirePermission('finance.view');
            return $container->get(FinanceController::class)->dashboard();
        });

        // Chart of Accounts
        $router->get('/chart-of-accounts', static function (Request $req) use ($container) {
            $req->user()->requirePermission('finance.view');
            return $container->get(FinanceController::class)->getChartOfAccounts();
        });
        
        $router->post('/chart-of-accounts', static function (Request $req) use ($container) {
            $req->user()->requirePermission('finance.manage');
            return $container->get(FinanceController::class)->createChartOfAccount($req->json());
        });

        // Financial Accounts
        $router->get('/accounts', static function (Request $req) use ($container) {
            $req->user()->requirePermission('finance.view');
            return $container->get(FinanceController::class)->getFinancialAccounts();
        });

        $router->post('/accounts', static function (Request $req) use ($container) {
            $req->user()->requirePermission('accounts.manage');
            return $container->get(FinanceController::class)->createFinancialAccount($req->json());
        });

        // General Ledger
        $router->get('/ledger', static function (Request $req) use ($container) {
            $req->user()->requirePermission('finance.view');
            return $container->get(FinanceController::class)->getLedger();
        });

        // Journal Entries
        $router->get('/journal-entries', static function (Request $req) use ($container) {
            $req->user()->requirePermission('finance.view');
            return $container->get(FinanceController::class)->getJournalEntries();
        });

        $router->post('/journal-entries', static function (Request $req) use ($container) {
            $req->user()->requirePermission('finance.create');
            return $container->get(FinanceController::class)->createJournalEntry($req->json(), $req->user()->id());
        });

        // Expenses
        $router->get('/expenses', static function (Request $req) use ($container) {
            $req->user()->requirePermission('finance.view');
            return $container->get(FinanceController::class)->getExpenses();
        });

        $router->post('/expenses', static function (Request $req) use ($container) {
            $req->user()->requirePermission('expenses.manage');
            return $container->get(FinanceController::class)->createExpense($req->json(), $req->user()->id());
        });

        // Revenue
        $router->get('/revenue', static function (Request $req) use ($container) {
            $req->user()->requirePermission('finance.view');
            return $container->get(FinanceController::class)->getRevenues();
        });

        $router->post('/revenue', static function (Request $req) use ($container) {
            $req->user()->requirePermission('revenue.manage');
            return $container->get(FinanceController::class)->createRevenue($req->json(), $req->user()->id());
        });
    });
};
