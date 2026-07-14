<?php

declare(strict_types=1);
namespace SkyFi\Finance\Controllers;

use SkyFi\Finance\Services\FinanceService;

final class FinanceController
{
    public function __construct(private readonly FinanceService $service) {}

    public function dashboard(): array
    {
        return $this->service->getDashboardStatistics();
    }

    public function getChartOfAccounts(): array
    {
        return $this->service->getChartOfAccounts();
    }

    public function createChartOfAccount(array $data): array
    {
        return $this->service->createChartOfAccount($data)->toArray();
    }

    public function getFinancialAccounts(): array
    {
        return $this->service->getFinancialAccounts();
    }

    public function createFinancialAccount(array $data): array
    {
        return $this->service->createFinancialAccount($data)->toArray();
    }

    public function getLedger(): array
    {
        return $this->service->getLedgerBalances();
    }

    public function getJournalEntries(): array
    {
        return $this->service->getJournalEntries([]);
    }

    public function createJournalEntry(array $data, int $userId): array
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);
        return $this->service->createJournalEntry($data, $lines, $userId)->toArray();
    }

    public function getExpenses(): array
    {
        return $this->service->getExpenses([]);
    }

    public function createExpense(array $data, int $userId): array
    {
        return $this->service->createExpense($data, $userId)->toArray();
    }

    public function getRevenues(): array
    {
        return $this->service->getRevenues([]);
    }

    public function createRevenue(array $data, int $userId): array
    {
        return $this->service->createRevenue($data, $userId)->toArray();
    }
}
