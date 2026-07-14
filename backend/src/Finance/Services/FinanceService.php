<?php

declare(strict_types=1);
namespace SkyFi\Finance\Services;

use SkyFi\Finance\Contracts\FinanceRepositoryContract;
use SkyFi\Finance\Models\ChartOfAccount;
use SkyFi\Finance\Models\FinancialAccount;
use SkyFi\Finance\Models\JournalEntry;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Shared\Exceptions\NotFoundException;

final class FinanceService
{
    public function __construct(private readonly FinanceRepositoryContract $repo) {}

    public function getChartOfAccounts(): array
    {
        return array_map(fn($m) => $m->toArray(), $this->repo->getChartOfAccounts());
    }

    public function createChartOfAccount(array $data): ChartOfAccount
    {
        return $this->repo->createChartOfAccount($data);
    }

    public function getFinancialAccounts(): array
    {
        return array_map(fn($m) => $m->toArray(), $this->repo->getFinancialAccounts());
    }

    public function createFinancialAccount(array $data): FinancialAccount
    {
        return $this->repo->createFinancialAccount($data);
    }

    public function getLedgerBalances(): array
    {
        return $this->repo->getLedgerBalances();
    }

    public function getJournalEntries(array $filters): array
    {
        return array_map(fn($m) => $m->toArray(), $this->repo->getJournalEntries($filters));
    }

    public function createJournalEntry(array $data, array $lines, int $userId): JournalEntry
    {
        return $this->repo->transaction(function() use ($data, $lines, $userId) {
            $totalDebit = 0.0;
            $totalCredit = 0.0;
            
            foreach ($lines as $line) {
                $totalDebit += (float) ($line['debit_amount'] ?? 0);
                $totalCredit += (float) ($line['credit_amount'] ?? 0);
            }
            
            if (abs($totalDebit - $totalCredit) > 0.001) {
                throw new ValidationException([
                    ['code' => 'imbalance', 'detail' => 'Debits and Credits must balance.']
                ]);
            }
            
            if (!isset($data['transaction_id'])) {
                $data['transaction_id'] = bin2hex(random_bytes(16));
            }
            $data['created_by'] = $userId;
            
            $entry = $this->repo->createJournalEntry($data, $lines);
            
            // Post to ledger
            foreach ($lines as $line) {
                $account = $this->repo->findChartOfAccount((int) $line['account_id']);
                if ($account) {
                    $this->repo->updateLedgerBalance(
                        $account->id(),
                        $account->normalBalance(),
                        (float) ($line['debit_amount'] ?? 0),
                        (float) ($line['credit_amount'] ?? 0)
                    );
                }
            }
            
            return $entry;
        });
    }

    public function getExpenses(array $filters): array
    {
        return array_map(fn($m) => $m->toArray(), $this->repo->getExpenses($filters));
    }

    public function createExpense(array $data, int $userId): \SkyFi\Finance\Models\Expense
    {
        return $this->repo->transaction(function() use ($data, $userId) {
            $data['created_by'] = $userId;
            $expense = $this->repo->createExpense($data);
            
            // Post journal entry
            // Debit expense account, credit financial (cash/bank) account
            $finAccount = $this->repo->findFinancialAccount((int)$data['financial_account_id']);
            $finAccountCOAId = $finAccount->toArray()['chart_of_account_id'];
            
            $this->createJournalEntry([
                'transaction_id' => bin2hex(random_bytes(16)),
                'description' => 'Expense: ' . $data['category'],
                'transaction_date' => $data['transaction_date'],
                'source_id' => $expense->id(),
                'source_type' => 'App\Models\Expense'
            ], [
                ['account_id' => $data['chart_of_account_id'], 'debit_amount' => $data['amount'], 'credit_amount' => null],
                ['account_id' => $finAccountCOAId, 'debit_amount' => null, 'credit_amount' => $data['amount']]
            ], $userId);
            
            // Update financial account balance
            $this->repo->updateFinancialAccount((int)$data['financial_account_id'], [
                'balance' => (float)$finAccount->balance() - (float)$data['amount']
            ]);
            
            return $expense;
        });
    }

    public function getRevenues(array $filters): array
    {
        return array_map(fn($m) => $m->toArray(), $this->repo->getRevenues($filters));
    }

    public function createRevenue(array $data, int $userId): \SkyFi\Finance\Models\Revenue
    {
        return $this->repo->transaction(function() use ($data, $userId) {
            $data['created_by'] = $userId;
            $revenue = $this->repo->createRevenue($data);
            
            // Post journal entry
            // Debit financial (cash/bank) account, credit revenue account
            $finAccount = $this->repo->findFinancialAccount((int)$data['financial_account_id']);
            $finAccountCOAId = $finAccount->toArray()['chart_of_account_id'];
            
            $this->createJournalEntry([
                'transaction_id' => bin2hex(random_bytes(16)),
                'description' => 'Revenue: ' . $data['category'],
                'transaction_date' => $data['transaction_date'],
                'source_id' => $revenue->id(),
                'source_type' => 'App\Models\Revenue'
            ], [
                ['account_id' => $finAccountCOAId, 'debit_amount' => $data['amount'], 'credit_amount' => null],
                ['account_id' => $data['chart_of_account_id'], 'debit_amount' => null, 'credit_amount' => $data['amount']]
            ], $userId);
            
            // Update financial account balance
            $this->repo->updateFinancialAccount((int)$data['financial_account_id'], [
                'balance' => (float)$finAccount->balance() + (float)$data['amount']
            ]);
            
            return $revenue;
        });
    }

    public function getDashboardStatistics(): array
    {
        return $this->repo->getDashboardStatistics();
    }
}
