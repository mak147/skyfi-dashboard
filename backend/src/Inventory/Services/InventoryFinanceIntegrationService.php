<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Services;

use PDO;
use SkyFi\Finance\Services\FinanceService;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class InventoryFinanceIntegrationService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly FinanceService $finance,
    ) {
    }

    public function tryPostMovement(int $movementId, int $actorId): void
    {
        try {
            $this->postMovement($movementId, $actorId);
        } catch (\Throwable $exception) {
            $this->pdo->prepare("UPDATE inventory_finance_postings SET status = 'failed', attempts = attempts + 1, last_error = ? WHERE movement_id = ? AND status <> 'posted'")->execute([mb_substr($exception->getMessage(), 0, 1000), $movementId]);
        }
    }

    /** @return array<string, mixed> */
    public function retry(int $postingId, int $actorId): array
    {
        $statement = $this->pdo->prepare('SELECT movement_id FROM inventory_finance_postings WHERE id = ?');
        $statement->execute([$postingId]);
        $movementId = $statement->fetchColumn();
        if ($movementId === false) {
            throw new NotFoundException('Inventory finance posting not found.');
        }
        $this->postMovement((int) $movementId, $actorId);
        $result = $this->pdo->prepare('SELECT * FROM inventory_finance_postings WHERE id = ?');
        $result->execute([$postingId]);
        return $result->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function postMovement(int $movementId, int $actorId): void
    {
        $statement = $this->pdo->prepare('SELECT fp.id AS posting_id, fp.status AS posting_status, m.*, COALESCE(SUM(l.total_cost), 0) AS total_cost FROM inventory_finance_postings fp JOIN inventory_stock_movements m ON m.id = fp.movement_id JOIN inventory_stock_movement_lines l ON l.movement_id = m.id WHERE fp.movement_id = ? GROUP BY fp.id, m.id');
        $statement->execute([$movementId]);
        $movement = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$movement) {
            throw new NotFoundException('Inventory movement finance posting not found.');
        }
        if (in_array($movement['posting_status'], ['posted', 'not_required'], true)) {
            return;
        }
        $amount = (float) $movement['total_cost'];
        if ($amount <= 0) {
            $this->pdo->prepare("UPDATE inventory_finance_postings SET status = 'not_required', attempts = attempts + 1, last_error = NULL WHERE id = ?")->execute([(int) $movement['posting_id']]);
            return;
        }
        $settings = $this->pdo->query('SELECT * FROM inventory_accounting_settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC) ?: [];
        $inventory = (int) ($settings['inventory_asset_account_id'] ?? 0);
        $clearing = (int) ($settings['inventory_clearing_account_id'] ?? 0);
        $cogs = (int) ($settings['cogs_account_id'] ?? 0);
        $adjustment = (int) ($settings['adjustment_account_id'] ?? 0);
        $damage = (int) ($settings['damage_scrap_account_id'] ?? 0);
        $type = (string) $movement['movement_type'];
        $debit = 0;
        $credit = 0;
        if (in_array($type, ['opening_balance', 'stock_in', 'return'], true)) {
            [$debit, $credit] = [$inventory, $clearing];
        } elseif ($type === 'adjustment_in') {
            [$debit, $credit] = [$inventory, $adjustment];
        } elseif ($type === 'stock_out') {
            [$debit, $credit] = [$cogs, $inventory];
        } elseif ($type === 'adjustment_out') {
            [$debit, $credit] = [$adjustment, $inventory];
        } elseif (in_array($type, ['damaged', 'scrap'], true)) {
            [$debit, $credit] = [$damage, $inventory];
        } elseif ($type === 'reversal') {
            $original = $this->pdo->prepare('SELECT je.id, jel.account_id, jel.debit_amount, jel.credit_amount FROM inventory_stock_movements m JOIN inventory_finance_postings fp ON fp.movement_id = m.reversal_of_id JOIN journal_entries je ON je.id = fp.journal_entry_id JOIN journal_entry_lines jel ON jel.journal_entry_id = je.id WHERE m.id = ?');
            $original->execute([$movementId]);
            $lines = [];
            foreach ($original->fetchAll(PDO::FETCH_ASSOC) as $line) {
                $lines[] = ['account_id' => (int) $line['account_id'], 'debit_amount' => $line['credit_amount'], 'credit_amount' => $line['debit_amount']];
            }
            if ($lines === []) {
                throw new ValidationException([['code' => 'finance_mapping_missing', 'detail' => 'The original financial posting must be completed before its reversal.']]);
            }
            $journal = $this->finance->createJournalEntry([
                'description' => 'Inventory reversal ' . $movement['movement_number'],
                'transaction_date' => substr((string) $movement['occurred_at'], 0, 10),
                'source_id' => $movementId,
                'source_type' => 'InventoryStockMovement',
            ], $lines, $actorId);
            $this->complete((int) $movement['posting_id'], $journal->id());
            return;
        } else {
            $this->pdo->prepare("UPDATE inventory_finance_postings SET status = 'not_required', attempts = attempts + 1 WHERE id = ?")->execute([(int) $movement['posting_id']]);
            return;
        }
        if ($debit < 1 || $credit < 1) {
            throw new ValidationException([['code' => 'finance_mapping_missing', 'detail' => 'Configure all required inventory accounting accounts before retrying this posting.']]);
        }
        $journal = $this->finance->createJournalEntry([
            'description' => 'Inventory ' . str_replace('_', ' ', $type) . ': ' . $movement['movement_number'],
            'transaction_date' => substr((string) $movement['occurred_at'], 0, 10),
            'source_id' => $movementId,
            'source_type' => 'InventoryStockMovement',
        ], [
            ['account_id' => $debit, 'debit_amount' => number_format($amount, 2, '.', ''), 'credit_amount' => null],
            ['account_id' => $credit, 'debit_amount' => null, 'credit_amount' => number_format($amount, 2, '.', '')],
        ], $actorId);
        $this->complete((int) $movement['posting_id'], $journal->id());
    }

    private function complete(int $postingId, int $journalEntryId): void
    {
        $this->pdo->prepare("UPDATE inventory_finance_postings SET journal_entry_id = ?, status = 'posted', attempts = attempts + 1, last_error = NULL, posted_at = UTC_TIMESTAMP() WHERE id = ?")->execute([$journalEntryId, $postingId]);
    }
}
