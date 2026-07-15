<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Services;

use PDO;

final class VendorDashboardService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed> */
    public function getDashboardWidgets(): array
    {
        // 1. Active Suppliers
        $activeStmt = $this->pdo->query("SELECT COUNT(*) FROM vendors WHERE status = 'active' AND deleted_at IS NULL");
        $activeCount = (int) $activeStmt->fetchColumn();

        $totalStmt = $this->pdo->query("SELECT COUNT(*) FROM vendors WHERE deleted_at IS NULL");
        $totalCount = (int) $totalStmt->fetchColumn();

        // 2. Expiring Contracts (expiring within 30 days or already expired)
        $expiringStmt = $this->pdo->query("SELECT COUNT(*) FROM vendor_contracts WHERE status IN ('expiring', 'expired') AND deleted_at IS NULL");
        $expiringCount = (int) $expiringStmt->fetchColumn();

        // 3. Average Supplier Rating
        $ratingStmt = $this->pdo->query("SELECT AVG(overall_rating) FROM vendors WHERE status = 'active' AND deleted_at IS NULL AND overall_rating > 0");
        $avgRating = round((float) ($ratingStmt->fetchColumn() ?? 0.0), 2);

        // 4. Procurement Spend by Supplier (Top Suppliers by spend)
        $spendStmt = $this->pdo->query(
            "SELECT v.id, v.code, v.name, v.category, v.overall_rating,
                    COUNT(po.id) AS po_count,
                    COALESCE(SUM(po.total_amount), 0) AS total_spend
             FROM vendors v
             LEFT JOIN purchase_orders po ON po.vendor_id = v.id AND po.status IN ('approved', 'sent', 'partially_received', 'fully_received', 'closed') AND po.deleted_at IS NULL
             WHERE v.deleted_at IS NULL
             GROUP BY v.id, v.code, v.name, v.category, v.overall_rating
             ORDER BY total_spend DESC, v.name ASC
             LIMIT 10"
        );
        $topSuppliers = $spendStmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total spend across all suppliers
        $totalSpendStmt = $this->pdo->query(
            "SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders WHERE status IN ('approved', 'sent', 'partially_received', 'fully_received', 'closed') AND deleted_at IS NULL"
        );
        $totalProcurementSpend = (float) $totalSpendStmt->fetchColumn();

        // 5. Contracts expiring soon list (for alerts)
        $alertContractsStmt = $this->pdo->query(
            "SELECT vc.id, vc.contract_number, vc.title, vc.end_date, vc.status, vc.contract_value, v.name AS vendor_name
             FROM vendor_contracts vc
             JOIN vendors v ON v.id = vc.vendor_id
             WHERE vc.status IN ('active', 'expiring') AND vc.deleted_at IS NULL AND vc.end_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY)
             ORDER BY vc.end_date ASC
             LIMIT 5"
        );
        $expiringContractsList = $alertContractsStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'active_suppliers' => $activeCount,
            'total_suppliers' => $totalCount,
            'expiring_contracts_count' => $expiringCount,
            'average_supplier_rating' => $avgRating,
            'total_procurement_spend' => $totalProcurementSpend,
            'top_suppliers' => $topSuppliers,
            'expiring_contracts_list' => $expiringContractsList,
        ];
    }
}
