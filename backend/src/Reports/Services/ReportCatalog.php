<?php

declare(strict_types=1);

namespace SkyFi\Reports\Services;

use SkyFi\Shared\Exceptions\NotFoundException;

final class ReportCatalog
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $groups = [
            'customer' => [['growth','Customer Growth','line'],['active','Active Customers','table'],['suspended','Suspended Customers','table'],['new-installations','New Installations','bar'],['churn','Customer Churn','line']],
            'connection' => [['active','Active Connections','table'],['types','Connection Types','pie'],['package-distribution','Package Distribution','bar']],
            'billing' => [['outstanding-invoices','Outstanding Invoices','table'],['paid-invoices','Paid Invoices','table'],['overdue-invoices','Overdue Invoices','table'],['revenue-by-month','Revenue by Month','line']],
            'payment' => [['collections','Collections','table'],['methods','Payment Methods','pie'],['daily-collections','Daily Collections','line'],['monthly-collections','Monthly Collections','bar']],
            'finance' => [['income-statement','Income Statement (Basic)','bar'],['expense-summary','Expense Summary','pie'],['cash-flow-summary','Cash Flow Summary','line'],['revenue-vs-expenses','Revenue vs Expenses','bar']],
            'inventory' => [['stock-levels','Stock Levels','table'],['low-stock','Low Stock','table'],['asset-assignment','Asset Assignment','table'],['asset-depreciation','Asset Depreciation','table']],
            'purchasing' => [['purchase-orders','Purchase Orders','table'],['goods-receipts','Goods Receipts','table'],['procurement-spend','Procurement Spend','bar']],
            'vendor' => [['supplier-performance','Supplier Performance','bar'],['supplier-spend','Supplier Spend','bar']],
            'support' => [['ticket-volume','Ticket Volume','line'],['sla-compliance','SLA Compliance','pie'],['resolution-time','Resolution Time','bar']],
            'network' => [['router-health','Router Health','table'],['infrastructure-capacity','Infrastructure Capacity','bar'],['pppoe-sessions','PPPoE Sessions','line'],['hotspot-sessions','Hotspot Sessions','line'],['bandwidth-usage','Bandwidth Usage','line']],
            'field' => [['installation-success-rate','Installation Success Rate','line'],['technician-productivity','Technician Productivity','bar'],['average-completion-time','Average Completion Time','line']],
        ];
        $filters = [
            'customer'=>['date_from','date_to','customer_id','region','package_id','technician_id','status'],
            'connection'=>['date_from','date_to','customer_id','region','pop_site_id','tower_id','package_id','technician_id','status'],
            'billing'=>['date_from','date_to','customer_id','region','package_id','status'],
            'payment'=>['date_from','date_to','customer_id','region','status'],
            'finance'=>['date_from','date_to'],
            'inventory'=>['date_from','date_to','supplier_id','warehouse_id','status'],
            'purchasing'=>['date_from','date_to','supplier_id','warehouse_id','status'],
            'vendor'=>['date_from','date_to','supplier_id','status'],
            'support'=>['date_from','date_to','customer_id','region','technician_id','status'],
            'network'=>['date_from','date_to','pop_site_id','tower_id','status'],
            'field'=>['date_from','date_to','customer_id','region','pop_site_id','tower_id','technician_id','status'],
        ];
        $result = [];
        foreach ($groups as $category => $reports) {
            foreach ($reports as [$slug,$name,$chart]) {
                $key = $category . '.' . $slug;
                $result[$key] = [
                    'key' => $key,
                    'name' => $name,
                    'category' => $category,
                    'description' => sprintf('%s metrics from the live operational data store.', $name),
                    'default_visualization' => $chart,
                    'filters' => $filters[$category],
                    'is_placeholder' => $key === 'inventory.asset-depreciation',
                ];
            }
        }
        return $result;
    }

    /** @return array<string, mixed> */
    public function get(string $key): array
    {
        return $this->all()[$key] ?? throw new NotFoundException('The requested report does not exist.');
    }

    /** @return array<int, array<string, mixed>> */
    public function grouped(): array
    {
        $groups = [];
        foreach ($this->all() as $report) {
            $groups[$report['category']][] = $report;
        }
        return array_map(static fn(string $category, array $reports): array => [
            'category' => $category,
            'label' => ucwords(str_replace('-', ' ', $category)),
            'reports' => $reports,
        ], array_keys($groups), array_values($groups));
    }
}
