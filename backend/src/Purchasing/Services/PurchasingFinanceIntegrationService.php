<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Services;

use PDO;
use SkyFi\Finance\Services\FinanceService;

/**
 * Handles finance posting placeholders for purchasing transactions.
 * Follows the same idempotent pattern as Inventory's InventoryFinanceIntegrationService.
 */
final class PurchasingFinanceIntegrationService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly FinanceService $finance,
    ) {
    }

    /**
     * Create a finance posting placeholder when a PO is approved (commitment).
     */
    public function tryPostOrderApproved(int $orderId, int $actorId): void
    {
        $idempotencyKey = 'purchasing_po_approved_' . $orderId;

        try {
            $stmt = $this->pdo->prepare(
                'INSERT IGNORE INTO purchasing_finance_postings (source_type, source_id, idempotency_key, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute(['purchase_order', $orderId, $idempotencyKey, 'not_required']);
        } catch (\PDOException $e) {
            // Silently fail — finance posting is non-critical
        }
    }

    /**
     * Create a finance posting when goods are received (liability).
     */
    public function tryPostReceipt(int $receiptId, int $actorId): void
    {
        $idempotencyKey = 'purchasing_gr_' . $receiptId;

        try {
            // Check if posting already exists
            $checkStmt = $this->pdo->prepare('SELECT id, status FROM purchasing_finance_postings WHERE source_type = ? AND source_id = ?');
            $checkStmt->execute(['goods_receipt', $receiptId]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing && $existing['status'] === 'posted') {
                return;
            }

            // Create or update posting
            $stmt = $this->pdo->prepare(
                'INSERT INTO purchasing_finance_postings (source_type, source_id, idempotency_key, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()'
            );
            $stmt->execute(['goods_receipt', $receiptId, $idempotencyKey, 'not_required']);

            // Future: when accounting mappings are configured, this would create
            // a journal entry: Debit Inventory Asset, Credit GRNI Clearing
            // For now, we mark as not_required since AP is out of scope.
        } catch (\PDOException $e) {
            // Silently fail — finance posting is non-critical
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function financePostings(): array
    {
        $stmt = $this->pdo->query(
            'SELECT pfp.*, je.description AS journal_description
             FROM purchasing_finance_postings pfp
             LEFT JOIN journal_entries je ON je.id = pfp.journal_entry_id
             ORDER BY pfp.created_at DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
