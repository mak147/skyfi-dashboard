<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Services;

use PDO;

final class PurchasingDashboardService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed> */
    public function dashboard(): array
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        // Open Purchase Orders
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM purchase_orders WHERE status IN ('approved', 'sent', 'partially_received') AND deleted_at IS NULL"
        );
        $openPoCount = (int) $stmt->fetchColumn();

        // Pending Approvals (requests + orders)
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM purchase_requests WHERE status = 'pending_approval' AND deleted_at IS NULL"
        );
        $pendingRequests = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM purchase_orders WHERE status = 'pending_approval' AND deleted_at IS NULL"
        );
        $pendingOrders = (int) $stmt->fetchColumn();

        // Goods Received Today
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM goods_receipts WHERE DATE(received_at) = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$today]);
        $receivedToday = (int) $stmt->fetchColumn();

        // Outstanding Deliveries (POs past expected delivery date)
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM purchase_orders WHERE expected_delivery_date < ? AND status IN ('approved', 'sent', 'partially_received') AND deleted_at IS NULL"
        );
        $stmt->execute([$today]);
        $outstanding = (int) $stmt->fetchColumn();

        // Procurement Spend (current month - approved POs)
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders WHERE order_date >= ? AND status NOT IN ('draft', 'rejected', 'cancelled') AND deleted_at IS NULL"
        );
        $stmt->execute([$monthStart]);
        $procurementSpend = (float) $stmt->fetchColumn();

        // Total procurement spend all time
        $stmt = $this->pdo->query(
            "SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders WHERE status NOT IN ('draft', 'rejected', 'cancelled') AND deleted_at IS NULL"
        );
        $totalSpend = (float) $stmt->fetchColumn();

        // Status breakdown
        $stmt = $this->pdo->query(
            "SELECT status, COUNT(*) AS total FROM purchase_orders WHERE deleted_at IS NULL GROUP BY status"
        );
        $poByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Request status breakdown
        $stmt = $this->pdo->query(
            "SELECT status, COUNT(*) AS total FROM purchase_requests WHERE deleted_at IS NULL GROUP BY status"
        );
        $reqByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent purchase orders
        $stmt = $this->pdo->query(
            "SELECT po.id, po.po_number, po.status, po.total_amount, po.order_date, po.expected_delivery_date,
                    v.name AS vendor_name
             FROM purchase_orders po
             LEFT JOIN vendors v ON v.id = po.vendor_id
             WHERE po.deleted_at IS NULL
             ORDER BY po.created_at DESC
             LIMIT 10"
        );
        $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent goods receipts
        $stmt = $this->pdo->query(
            "SELECT gr.id, gr.receipt_number, gr.status, gr.received_at, po.po_number, v.name AS vendor_name
             FROM goods_receipts gr
             LEFT JOIN purchase_orders po ON po.id = gr.purchase_order_id
             LEFT JOIN vendors v ON v.id = po.vendor_id
             WHERE gr.deleted_at IS NULL
             ORDER BY gr.received_at DESC
             LIMIT 5"
        );
        $recentReceipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Monthly spend trend (last 6 months)
        $monthlySpend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $monthStartStr = date('Y-m-01', strtotime("-{$i} months"));
            $monthEndStr = date('Y-m-t', strtotime("-{$i} months"));
            $stmt = $this->pdo->prepare(
                "SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders WHERE order_date >= ? AND order_date <= ? AND status NOT IN ('draft', 'rejected', 'cancelled') AND deleted_at IS NULL"
            );
            $stmt->execute([$monthStartStr, $monthEndStr]);
            $monthlySpend[] = ['month' => $month, 'amount' => (float) $stmt->fetchColumn()];
        }

        return [
            'open_purchase_orders' => $openPoCount,
            'pending_approvals' => $pendingRequests + $pendingOrders,
            'pending_request_approvals' => $pendingRequests,
            'pending_order_approvals' => $pendingOrders,
            'goods_received_today' => $receivedToday,
            'outstanding_deliveries' => $outstanding,
            'procurement_spend_month' => $procurementSpend,
            'procurement_spend_total' => $totalSpend,
            'po_by_status' => $poByStatus,
            'requests_by_status' => $reqByStatus,
            'recent_orders' => $recentOrders,
            'recent_receipts' => $recentReceipts,
            'monthly_spend' => $monthlySpend,
        ];
    }
}
