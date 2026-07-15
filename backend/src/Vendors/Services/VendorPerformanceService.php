<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Services;

use PDO;

final class VendorPerformanceService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed> */
    public function getMetrics(int $vendorId): array
    {
        // 1. Order completion & total spend from Purchase Orders
        $poStmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total_orders,
                    SUM(CASE WHEN status IN ('fully_received', 'closed') THEN 1 ELSE 0 END) AS completed_orders,
                    SUM(CASE WHEN status IN ('approved', 'sent', 'partially_received', 'fully_received', 'closed') THEN total_amount ELSE 0 END) AS procurement_value
             FROM purchase_orders
             WHERE vendor_id = ? AND deleted_at IS NULL"
        );
        $poStmt->execute([$vendorId]);
        $poData = $poStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_orders' => 0, 'completed_orders' => 0, 'procurement_value' => 0];

        $totalOrders = (int) $poData['total_orders'];
        $completedOrders = (int) $poData['completed_orders'];
        $procurementValue = (float) $poData['procurement_value'];
        $orderCompletionPct = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 2) : 100.00;

        // 2. Return rate & product quality from Goods Receipts Items
        $grStmt = $this->pdo->prepare(
            "SELECT SUM(gri.quantity_accepted) AS accepted,
                    SUM(gri.quantity_damaged) AS damaged,
                    SUM(gri.quantity_short) AS short_qty
             FROM goods_receipt_items gri
             JOIN goods_receipts gr ON gr.id = gri.goods_receipt_id
             JOIN purchase_orders po ON po.id = gr.purchase_order_id
             WHERE po.vendor_id = ? AND gr.deleted_at IS NULL"
        );
        $grStmt->execute([$vendorId]);
        $grData = $grStmt->fetch(PDO::FETCH_ASSOC) ?: ['accepted' => 0, 'damaged' => 0, 'short_qty' => 0];

        $accepted = (float) $grData['accepted'];
        $damaged = (float) $grData['damaged'];
        $shortQty = (float) $grData['short_qty'];
        $totalReceived = $accepted + $damaged + $shortQty;

        $returnRate = $totalReceived > 0 ? round(($damaged / $totalReceived) * 100, 2) : 0.00;
        $productQuality = $totalReceived > 0 ? round(($accepted / $totalReceived) * 100, 2) : 100.00;

        // 3. Average lead time days from product catalog sync
        $ltStmt = $this->pdo->prepare(
            'SELECT AVG(lead_time_days) FROM inventory_product_vendors WHERE vendor_id = ? AND lead_time_days > 0'
        );
        $ltStmt->execute([$vendorId]);
        $avgLeadTime = round((float) ($ltStmt->fetchColumn() ?? 7.0), 1);

        // 4. Latest manual rating or overall score
        $ratStmt = $this->pdo->prepare('SELECT overall_rating FROM vendors WHERE id = ?');
        $ratStmt->execute([$vendorId]);
        $overallRating = round((float) ($ratStmt->fetchColumn() ?? 5.00), 2);

        return [
            'vendor_id' => $vendorId,
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'order_completion' => $orderCompletionPct,
            'procurement_value' => $procurementValue,
            'return_rate' => $returnRate,
            'product_quality' => $productQuality,
            'delivery_performance' => round(100.00 - ($shortQty > 0 && $totalReceived > 0 ? ($shortQty / $totalReceived) * 100 : 0.00), 2),
            'average_lead_time_days' => $avgLeadTime,
            'overall_rating' => $overallRating,
        ];
    }
}
