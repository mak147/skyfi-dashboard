<?php

declare(strict_types=1);
namespace SkyFi\Finance\Repositories;

use SkyFi\Finance\Contracts\FinanceRepositoryContract;
use SkyFi\Finance\Models\ChartOfAccount;
use SkyFi\Finance\Models\FinancialAccount;
use SkyFi\Finance\Models\JournalEntry;
use SkyFi\Finance\Models\Expense;
use SkyFi\Finance\Models\Revenue;
use PDO;

final class PdoFinanceRepository implements FinanceRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function transaction(callable $callback): mixed
    {
        if ($this->pdo->inTransaction()) return $callback();
        $this->pdo->beginTransaction();
        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getChartOfAccounts(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM chart_of_accounts ORDER BY account_number ASC');
        return array_map(fn($row) => ChartOfAccount::fromRow($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findChartOfAccount(int $id): ?ChartOfAccount
    {
        $stmt = $this->pdo->prepare('SELECT * FROM chart_of_accounts WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ChartOfAccount::fromRow($row) : null;
    }

    public function createChartOfAccount(array $data): ChartOfAccount
    {
        $stmt = $this->pdo->prepare('INSERT INTO chart_of_accounts (account_number, name, type, normal_balance, parent_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['account_number'],
            $data['name'],
            $data['type'],
            $data['normal_balance'],
            $data['parent_id'] ?? null
        ]);
        return $this->findChartOfAccount((int) $this->pdo->lastInsertId());
    }

    public function getFinancialAccounts(): array
    {
        $stmt = $this->pdo->query('SELECT f.*, c.name as chart_of_account_name FROM financial_accounts f JOIN chart_of_accounts c ON f.chart_of_account_id = c.id WHERE f.deleted_at IS NULL ORDER BY f.id DESC');
        return array_map(fn($row) => FinancialAccount::fromRow($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findFinancialAccount(int $id): ?FinancialAccount
    {
        $stmt = $this->pdo->prepare('SELECT f.*, c.name as chart_of_account_name FROM financial_accounts f JOIN chart_of_accounts c ON f.chart_of_account_id = c.id WHERE f.id = ? AND f.deleted_at IS NULL');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? FinancialAccount::fromRow($row) : null;
    }

    public function createFinancialAccount(array $data): FinancialAccount
    {
        $stmt = $this->pdo->prepare('INSERT INTO financial_accounts (account_type, name, chart_of_account_id, balance, currency, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['account_type'],
            $data['name'],
            $data['chart_of_account_id'],
            $data['balance'] ?? 0.00,
            $data['currency'] ?? 'PKR',
            $data['status'] ?? 'active'
        ]);
        return $this->findFinancialAccount((int) $this->pdo->lastInsertId());
    }

    public function updateFinancialAccount(int $id, array $data): FinancialAccount
    {
        $sets = [];
        $params = [];
        foreach ($data as $key => $val) {
            $sets[] = "$key = ?";
            $params[] = $val;
        }
        $params[] = $id;
        $stmt = $this->pdo->prepare('UPDATE financial_accounts SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);
        return $this->findFinancialAccount($id);
    }

    public function getLedgerBalances(): array
    {
        $stmt = $this->pdo->query('SELECT gl.*, c.account_number, c.name as account_name, c.type, c.normal_balance FROM general_ledger gl JOIN chart_of_accounts c ON gl.account_id = c.id ORDER BY c.account_number ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateLedgerBalance(int $accountId, string $normalBalance, float $debit, float $credit): void
    {
        // First check if row exists locking it
        $stmt = $this->pdo->prepare('SELECT balance FROM general_ledger WHERE account_id = ? FOR UPDATE');
        $stmt->execute([$accountId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $change = 0.00;
        if ($normalBalance === 'debit') {
            $change = $debit - $credit;
        } else {
            $change = $credit - $debit;
        }

        if ($row) {
            $upd = $this->pdo->prepare('UPDATE general_ledger SET balance = balance + ? WHERE account_id = ?');
            $upd->execute([$change, $accountId]);
        } else {
            $ins = $this->pdo->prepare('INSERT INTO general_ledger (account_id, balance) VALUES (?, ?)');
            $ins->execute([$accountId, $change]);
        }
    }

    public function getJournalEntries(array $filters): array
    {
        $sql = 'SELECT je.*, u.name as created_by_name FROM journal_entries je JOIN users u ON u.id = je.created_by ORDER BY je.id DESC LIMIT 100';
        $stmt = $this->pdo->query($sql);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($entries as &$entry) {
            $lineStmt = $this->pdo->prepare('SELECT jel.*, c.account_number, c.name as account_name FROM journal_entry_lines jel JOIN chart_of_accounts c ON jel.account_id = c.id WHERE jel.journal_entry_id = ?');
            $lineStmt->execute([$entry['id']]);
            $entry['lines'] = $lineStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return array_map(fn($row) => JournalEntry::fromRow($row), $entries);
    }

    public function findJournalEntry(int $id): ?JournalEntry
    {
        $stmt = $this->pdo->prepare('SELECT * FROM journal_entries WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        
        $lineStmt = $this->pdo->prepare('SELECT jel.*, c.account_number, c.name as account_name FROM journal_entry_lines jel JOIN chart_of_accounts c ON jel.account_id = c.id WHERE jel.journal_entry_id = ?');
        $lineStmt->execute([$id]);
        $row['lines'] = $lineStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return JournalEntry::fromRow($row);
    }

    public function createJournalEntry(array $data, array $lines): JournalEntry
    {
        $stmt = $this->pdo->prepare('INSERT INTO journal_entries (transaction_id, description, transaction_date, source_id, source_type, created_by) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['transaction_id'],
            $data['description'],
            $data['transaction_date'],
            $data['source_id'] ?? null,
            $data['source_type'] ?? null,
            $data['created_by']
        ]);
        
        $entryId = (int) $this->pdo->lastInsertId();
        
        $lineStmt = $this->pdo->prepare('INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit_amount, credit_amount) VALUES (?, ?, ?, ?)');
        foreach ($lines as $line) {
            $lineStmt->execute([
                $entryId,
                $line['account_id'],
                $line['debit_amount'] ?? null,
                $line['credit_amount'] ?? null
            ]);
        }
        
        return $this->findJournalEntry($entryId);
    }

    public function getExpenses(array $filters): array
    {
        $stmt = $this->pdo->query('SELECT e.*, f.name as financial_account_name, c.name as chart_of_account_name FROM expenses e JOIN financial_accounts f ON e.financial_account_id = f.id JOIN chart_of_accounts c ON e.chart_of_account_id = c.id ORDER BY e.id DESC LIMIT 100');
        return array_map(fn($row) => Expense::fromRow($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function createExpense(array $data): Expense
    {
        $stmt = $this->pdo->prepare('INSERT INTO expenses (category, amount, transaction_date, description, financial_account_id, chart_of_account_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['category'],
            $data['amount'],
            $data['transaction_date'],
            $data['description'] ?? null,
            $data['financial_account_id'],
            $data['chart_of_account_id'],
            $data['created_by']
        ]);
        
        $id = (int) $this->pdo->lastInsertId();
        $st = $this->pdo->prepare('SELECT * FROM expenses WHERE id = ?');
        $st->execute([$id]);
        return Expense::fromRow($st->fetch(PDO::FETCH_ASSOC));
    }

    public function getRevenues(array $filters): array
    {
        $stmt = $this->pdo->query('SELECT r.*, f.name as financial_account_name, c.name as chart_of_account_name FROM revenue r JOIN financial_accounts f ON r.financial_account_id = f.id JOIN chart_of_accounts c ON r.chart_of_account_id = c.id ORDER BY r.id DESC LIMIT 100');
        return array_map(fn($row) => Revenue::fromRow($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function createRevenue(array $data): Revenue
    {
        $stmt = $this->pdo->prepare('INSERT INTO revenue (category, amount, transaction_date, description, financial_account_id, chart_of_account_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['category'],
            $data['amount'],
            $data['transaction_date'],
            $data['description'] ?? null,
            $data['financial_account_id'],
            $data['chart_of_account_id'],
            $data['created_by']
        ]);
        
        $id = (int) $this->pdo->lastInsertId();
        $st = $this->pdo->prepare('SELECT * FROM revenue WHERE id = ?');
        $st->execute([$id]);
        return Revenue::fromRow($st->fetch(PDO::FETCH_ASSOC));
    }

    public function getDashboardStatistics(): array
    {
        $stats = [];
        
        // Cash Balance
        $cashStmt = $this->pdo->query("SELECT COALESCE(SUM(balance), 0) as total FROM financial_accounts WHERE account_type = 'cash' AND deleted_at IS NULL AND status = 'active'");
        $stats['cash_balance'] = $cashStmt->fetchColumn();
        
        // Bank Balance
        $bankStmt = $this->pdo->query("SELECT COALESCE(SUM(balance), 0) as total FROM financial_accounts WHERE account_type IN ('bank', 'merchant') AND deleted_at IS NULL AND status = 'active'");
        $stats['bank_balance'] = $bankStmt->fetchColumn();
        
        // Revenue This Month
        $revStmt = $this->pdo->query("SELECT COALESCE(SUM(amount), 0) FROM revenue WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())");
        $stats['revenue_this_month'] = $revStmt->fetchColumn();
        
        // Expenses This Month
        $expStmt = $this->pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())");
        $stats['expenses_this_month'] = $expStmt->fetchColumn();
        
        return $stats;
    }
}
