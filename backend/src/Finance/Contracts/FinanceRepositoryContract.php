<?php

declare(strict_types=1);
namespace SkyFi\Finance\Contracts;

use SkyFi\Finance\Models\ChartOfAccount;
use SkyFi\Finance\Models\FinancialAccount;
use SkyFi\Finance\Models\JournalEntry;

interface FinanceRepositoryContract
{
    public function transaction(callable $callback): mixed;
    
    // Chart of Accounts
    public function getChartOfAccounts(): array;
    public function findChartOfAccount(int $id): ?ChartOfAccount;
    public function createChartOfAccount(array $data): ChartOfAccount;
    
    // Financial Accounts
    public function getFinancialAccounts(): array;
    public function findFinancialAccount(int $id): ?FinancialAccount;
    public function createFinancialAccount(array $data): FinancialAccount;
    public function updateFinancialAccount(int $id, array $data): FinancialAccount;
    
    // General Ledger
    public function getLedgerBalances(): array;
    public function updateLedgerBalance(int $accountId, string $normalBalance, float $debit, float $credit): void;
    
    // Journal Entries
    public function getJournalEntries(array $filters): array;
    public function findJournalEntry(int $id): ?JournalEntry;
    public function createJournalEntry(array $data, array $lines): JournalEntry;
    
    // Expenses & Revenue
    public function getExpenses(array $filters): array;
    public function createExpense(array $data): \SkyFi\Finance\Models\Expense;
    public function getRevenues(array $filters): array;
    public function createRevenue(array $data): \SkyFi\Finance\Models\Revenue;
    
    // Dashboard / Reporting
    public function getDashboardStatistics(): array;
}
