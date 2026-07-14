<?php

declare(strict_types=1);

namespace SkyFi\Dashboard\Services;

use SkyFi\Dashboard\Contracts\DashboardServiceContract;
use SkyFi\Dashboard\Data\DashboardPayload;
use SkyFi\Shared\Exceptions\AuthorizationException;

final class DashboardService implements DashboardServiceContract
{
    private const CACHE_TTL_SECONDS = 300;

    /**
     * @param array<int, string> $roles Role names from the validated JWT claims.
     */
    public function dashboardForRoles(array $roles): DashboardPayload
    {
        $normalizedRoles = $this->normalizeRoles($roles);
        if ($normalizedRoles === []) {
            throw new AuthorizationException('A staff role is required to view the dashboard.');
        }

        $scope = $this->scopeForRoles($normalizedRoles);

        return new DashboardPayload(
            $scope['key'],
            $scope['title'],
            $scope['description'],
            $normalizedRoles,
            $this->widgetsForScope($scope['key']),
            self::CACHE_TTL_SECONDS,
        );
    }

    /** @param array<int, string> $roles @return array<int, string> */
    private function normalizeRoles(array $roles): array
    {
        $normalized = [];
        foreach ($roles as $role) {
            $role = trim($role);
            if ($role !== '' && !in_array($role, $normalized, true)) {
                $normalized[] = $role;
            }
        }

        return $normalized;
    }

    /** @param array<int, string> $roles @return array{key: string, title: string, description: string} */
    private function scopeForRoles(array $roles): array
    {
        $scopes = [
            'Super Administrator' => [
                'key' => 'executive',
                'title' => 'Executive Dashboard',
                'description' => 'Company-wide KPIs, revenue trends, subscriber growth, high-priority support, and network health.',
            ],
            'Company Owner' => [
                'key' => 'executive',
                'title' => 'Executive Dashboard',
                'description' => 'Company-wide KPIs, revenue trends, subscriber growth, high-priority support, and network health.',
            ],
            'Regional Manager' => [
                'key' => 'regional-operations',
                'title' => 'Regional Operations Dashboard',
                'description' => 'Regional subscribers, overdue work, support escalations, and field service performance.',
            ],
            'Finance Department' => [
                'key' => 'finance',
                'title' => 'Finance Dashboard',
                'description' => 'Billing-cycle readiness, collection health, overdue invoices, and payment activity.',
            ],
            'Customer Support' => [
                'key' => 'support',
                'title' => 'Support Dashboard',
                'description' => 'Ticket ownership, queue pressure, response-time trends, and customer activation watchlist.',
            ],
            'Network Engineer' => [
                'key' => 'network',
                'title' => 'Network Operations Dashboard',
                'description' => 'Router availability, tower alarms, PPPoE sessions, bandwidth load, and provisioning failures.',
            ],
            'Installation Team / Field Technician' => [
                'key' => 'field',
                'title' => 'Field Operations Dashboard',
                'description' => 'Assigned work orders, site survey readiness, SLA risk, and vehicle inventory reminders.',
            ],
            'Sales Team' => [
                'key' => 'sales',
                'title' => 'Sales Dashboard',
                'description' => 'Lead pipeline, quote follow-up, availability checks, and new customer conversion signals.',
            ],
            'Inventory Manager' => [
                'key' => 'inventory',
                'title' => 'Inventory Dashboard',
                'description' => 'Stock health, transfer workload, purchase-order activity, and warehouse exceptions.',
            ],
        ];

        foreach (array_keys($scopes) as $role) {
            if (in_array($role, $roles, true)) {
                return $scopes[$role];
            }
        }

        return [
            'key' => 'staff',
            'title' => 'Staff Dashboard',
            'description' => 'A focused operational dashboard for authenticated SkyFi staff roles.',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function widgetsForScope(string $scope): array
    {
        return match ($scope) {
            'executive' => $this->executiveWidgets(),
            'regional-operations' => $this->regionalWidgets(),
            'finance' => $this->financeWidgets(),
            'support' => $this->supportWidgets(),
            'network' => $this->networkWidgets(),
            'field' => $this->fieldWidgets(),
            'sales' => $this->salesWidgets(),
            'inventory' => $this->inventoryWidgets(),
            default => $this->staffWidgets(),
        };
    }

    /** @return array<int, array<string, mixed>> */
    private function executiveWidgets(): array
    {
        return [
            $this->stat('mrr', 'Monthly recurring revenue', '$52,480', '+2.5%', 'up', 'emerald', 'Recurring revenue recognized for the current month.'),
            $this->stat('active-subscribers', 'Active subscribers', '1,250', '+12 this month', 'up', 'indigo', 'Customers with at least one active service.'),
            $this->stat('monthly-churn', 'Monthly churn', '1.8%', '-0.3 pts', 'down', 'emerald', 'Subscriber churn compared with the previous month.'),
            $this->stat('open-tickets', 'Open tickets', '15', '3 urgent', 'neutral', 'amber', 'Tickets still requiring staff action.'),
            $this->chart('revenue-expenses', 'Revenue vs. Expenses', 'bar', ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], [
                ['label' => 'Revenue', 'data' => [42000, 45600, 47150, 48900, 51200, 52480]],
                ['label' => 'Expenses', 'data' => [28500, 29200, 30100, 31850, 32700, 33450]],
            ]),
            $this->chart('subscriber-growth', 'Subscriber Growth Trend', 'line', ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], [
                ['label' => 'Subscribers', 'data' => [1135, 1168, 1194, 1211, 1238, 1250]],
            ]),
            $this->list('priority-tickets', 'High-Priority Open Tickets', [
                ['id' => 'TCK-1042', 'primaryText' => 'Tower B packet loss escalation', 'secondaryText' => 'Network • 18 minutes ago', 'status' => 'Urgent'],
                ['id' => 'TCK-1038', 'primaryText' => 'Enterprise customer outage', 'secondaryText' => 'Support • 42 minutes ago', 'status' => 'High'],
                ['id' => 'TCK-1031', 'primaryText' => 'Repeated failed payment complaint', 'secondaryText' => 'Billing • 2 hours ago', 'status' => 'High'],
            ]),
            $this->gauge('network-health', 'System-wide network health', 96, 100, '%', 'emerald'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function regionalWidgets(): array
    {
        return [
            $this->stat('regional-subscribers', 'Regional subscribers', '428', '+9 this month', 'up', 'indigo', 'Active subscribers in the assigned region.'),
            $this->stat('regional-overdue', 'Overdue invoices', '23', '-4 this week', 'down', 'emerald', 'Invoices past due in the assigned region.'),
            $this->stat('open-work-orders', 'Open work orders', '18', '5 due today', 'neutral', 'amber', 'Scheduled work still in progress.'),
            $this->stat('regional-tickets', 'Open tickets', '9', '2 escalated', 'neutral', 'red', 'Support issues affecting regional customers.'),
            $this->chart('regional-service-mix', 'Regional Service Mix', 'doughnut', ['Residential', 'Business', 'Enterprise'], [
                ['label' => 'Services', 'data' => [318, 82, 28]],
            ]),
            $this->list('regional-escalations', 'Regional Escalations', [
                ['id' => 'REG-18', 'primaryText' => 'North sector install backlog', 'secondaryText' => '5 work orders waiting on tower clearance', 'status' => 'Watch'],
                ['id' => 'REG-16', 'primaryText' => 'Collections follow-up batch', 'secondaryText' => '12 customers need payment contact', 'status' => 'Action'],
                ['id' => 'REG-12', 'primaryText' => 'Support SLA risk', 'secondaryText' => '2 tickets approach breach window', 'status' => 'Risk'],
            ]),
            $this->gauge('field-sla', 'Field SLA attainment', 88, 100, '%', 'amber'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function financeWidgets(): array
    {
        return [
            $this->stat('billing-readiness', 'Billing readiness', '94%', '+6 pts', 'up', 'emerald', 'Accounts ready for the next billing run.'),
            $this->stat('overdue-invoices', 'Overdue invoices', '87', '-11 this week', 'down', 'emerald', 'Invoices requiring collection follow-up.'),
            $this->stat('payments-today', 'Payments today', '$8,940', '+18%', 'up', 'indigo', 'Payments recorded during the current business day.'),
            $this->chart('collection-health', 'Collection Health', 'bar', ['Current', '1-30', '31-60', '61+'], [
                ['label' => 'Invoices', 'data' => [410, 52, 21, 14]],
            ]),
            $this->list('finance-followups', 'Collection Follow-Ups', [
                ['id' => 'FIN-21', 'primaryText' => 'Large account promised payment', 'secondaryText' => 'Due by end of day', 'status' => 'Due'],
                ['id' => 'FIN-19', 'primaryText' => 'Manual payment review', 'secondaryText' => '3 records need confirmation', 'status' => 'Review'],
                ['id' => 'FIN-14', 'primaryText' => 'Credit memo approval queue', 'secondaryText' => '2 pending approvals', 'status' => 'Pending'],
            ]),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function supportWidgets(): array
    {
        return [
            $this->stat('my-open-tickets', 'My open tickets', '7', '2 due soon', 'neutral', 'indigo', 'Tickets assigned to the signed-in support agent.'),
            $this->stat('unassigned-queue', 'Unassigned queue', '11', '+3 today', 'up', 'amber', 'Tickets waiting for assignment.'),
            $this->stat('first-response', 'Avg. first response', '18m', '-4m', 'down', 'emerald', 'Average first response time for today.'),
            $this->list('my-ticket-updates', 'My Recently Updated Tickets', [
                ['id' => 'TCK-1088', 'primaryText' => 'Slow evening speeds', 'secondaryText' => 'Maria Cruz • updated 8 minutes ago', 'status' => 'Open'],
                ['id' => 'TCK-1081', 'primaryText' => 'Router replacement request', 'secondaryText' => 'Avi Singh • updated 21 minutes ago', 'status' => 'Waiting'],
                ['id' => 'TCK-1074', 'primaryText' => 'Payment receipt missing', 'secondaryText' => 'Lina Ortega • updated 1 hour ago', 'status' => 'Open'],
            ]),
            $this->list('recent-activations', 'Recent Customer Activations', [
                ['id' => 'ACT-211', 'primaryText' => 'Ramos Family', 'secondaryText' => 'Residential 50 Mbps • activated today', 'status' => 'New'],
                ['id' => 'ACT-209', 'primaryText' => 'Cedar Cafe', 'secondaryText' => 'Business 100 Mbps • activated yesterday', 'status' => 'New'],
            ]),
            $this->chart('ticket-flow', 'Ticket Inflow vs. Outflow', 'line', ['08:00', '10:00', '12:00', '14:00', '16:00'], [
                ['label' => 'Created', 'data' => [3, 5, 7, 8, 11]],
                ['label' => 'Resolved', 'data' => [2, 4, 5, 7, 10]],
            ]),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function networkWidgets(): array
    {
        return [
            $this->stat('routers-online', 'Routers online', '42 / 43', '1 degraded', 'neutral', 'amber', 'MikroTik routers reporting healthy status.'),
            $this->stat('tower-alarms', 'Towers with alarms', '2', '+1 today', 'up', 'red', 'Towers with active network alarms.'),
            $this->stat('pppoe-sessions', 'Active PPPoE sessions', '1,184', '+36', 'up', 'indigo', 'Authenticated PPPoE sessions across the network.'),
            $this->chart('bandwidth-throughput', 'System Bandwidth Throughput', 'line', ['Now-20m', 'Now-15m', 'Now-10m', 'Now-5m', 'Now'], [
                ['label' => 'Down Mbps', 'data' => [620, 680, 710, 760, 735]],
                ['label' => 'Up Mbps', 'data' => [210, 225, 240, 255, 248]],
            ]),
            $this->list('device-pressure', 'Devices with High CPU / Memory', [
                ['id' => 'RTR-07', 'primaryText' => 'Core Router 07', 'secondaryText' => 'CPU 84% • memory 72%', 'status' => 'High'],
                ['id' => 'TWR-12', 'primaryText' => 'Tower 12 backhaul', 'secondaryText' => 'CPU 76% • radio retries elevated', 'status' => 'Watch'],
            ]),
            $this->list('provisioning-failures', 'Recent Provisioning Failures', [
                ['id' => 'PRV-51', 'primaryText' => 'PPPoE profile sync failed', 'secondaryText' => 'Customer #4081 • retry queued', 'status' => 'Retry'],
                ['id' => 'PRV-48', 'primaryText' => 'Router API timeout', 'secondaryText' => 'North POP • 36 minutes ago', 'status' => 'Timeout'],
            ]),
            $this->gauge('network-capacity', 'Network capacity available', 72, 100, '%', 'indigo'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function fieldWidgets(): array
    {
        return [
            $this->stat('assigned-work-orders', 'Assigned work orders', '6', '3 due today', 'neutral', 'indigo', 'Work orders currently assigned to the technician.'),
            $this->stat('site-surveys', 'Site surveys ready', '4', '+1 new', 'up', 'emerald', 'Site surveys ready to execute.'),
            $this->stat('vehicle-stock-alerts', 'Vehicle stock alerts', '2', 'restock needed', 'neutral', 'amber', 'Inventory items below vehicle minimum.'),
            $this->list('field-schedule', 'Today\'s Field Schedule', [
                ['id' => 'WO-772', 'primaryText' => 'New install: Rivera Residence', 'secondaryText' => '09:00 • Sector East', 'status' => 'Scheduled'],
                ['id' => 'WO-775', 'primaryText' => 'Repair: radio realignment', 'secondaryText' => '12:30 • Tower B coverage area', 'status' => 'Scheduled'],
                ['id' => 'WO-781', 'primaryText' => 'Site survey: Hilltop Retail', 'secondaryText' => '15:00 • Needs LOS check', 'status' => 'Survey'],
            ]),
            $this->gauge('field-sla-risk', 'On-time work order target', 91, 100, '%', 'emerald'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function salesWidgets(): array
    {
        return [
            $this->stat('new-leads', 'New leads', '24', '+7 today', 'up', 'indigo', 'Leads added during the current week.'),
            $this->stat('quotes-pending', 'Quotes pending', '13', '4 need follow-up', 'neutral', 'amber', 'Quotes awaiting customer response.'),
            $this->stat('availability-checks', 'Availability checks', '31', '+12%', 'up', 'emerald', 'Service availability checks completed this week.'),
            $this->chart('pipeline-stage', 'Pipeline by Stage', 'doughnut', ['Lead', 'Qualified', 'Quoted', 'Won'], [
                ['label' => 'Opportunities', 'data' => [24, 18, 13, 7]],
            ]),
            $this->list('sales-followups', 'Priority Follow-Ups', [
                ['id' => 'SAL-34', 'primaryText' => 'Cedar Apartments bulk quote', 'secondaryText' => 'Decision expected tomorrow', 'status' => 'Hot'],
                ['id' => 'SAL-31', 'primaryText' => 'Hilltop Retail service check', 'secondaryText' => 'Needs site survey coordination', 'status' => 'Action'],
            ]),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function inventoryWidgets(): array
    {
        return [
            $this->stat('low-stock-items', 'Low-stock items', '9', '+2 this week', 'up', 'amber', 'Inventory items below reorder threshold.'),
            $this->stat('open-transfers', 'Open transfers', '6', '2 delayed', 'neutral', 'red', 'Stock transfers not yet completed.'),
            $this->stat('purchase-orders', 'Purchase orders', '5', '1 awaiting receipt', 'neutral', 'indigo', 'Open purchase orders in progress.'),
            $this->chart('warehouse-stock', 'Warehouse Stock Health', 'bar', ['Main', 'North', 'South', 'Vehicles'], [
                ['label' => 'Healthy SKUs', 'data' => [88, 74, 69, 52]],
                ['label' => 'Low SKUs', 'data' => [6, 4, 5, 9]],
            ]),
            $this->list('inventory-exceptions', 'Inventory Exceptions', [
                ['id' => 'INV-18', 'primaryText' => '5 GHz radios below minimum', 'secondaryText' => 'Main warehouse • reorder recommended', 'status' => 'Low'],
                ['id' => 'INV-15', 'primaryText' => 'Fiber drop cable transfer delayed', 'secondaryText' => 'North warehouse • vendor ETA pending', 'status' => 'Delay'],
            ]),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function staffWidgets(): array
    {
        return [
            $this->stat('assigned-actions', 'Assigned actions', '5', '2 due today', 'neutral', 'indigo', 'Open work items assigned to the signed-in staff member.'),
            $this->stat('team-alerts', 'Team alerts', '3', '1 urgent', 'neutral', 'amber', 'Operational alerts related to your role.'),
            $this->list('staff-next-steps', 'Recommended Next Steps', [
                ['id' => 'NEXT-1', 'primaryText' => 'Review your assigned queue', 'secondaryText' => 'Prioritize due-today work first', 'status' => 'Suggested'],
                ['id' => 'NEXT-2', 'primaryText' => 'Check pending notifications', 'secondaryText' => 'Follow escalations before SLA windows', 'status' => 'Suggested'],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private function stat(string $id, string $title, string $value, string $change, string $trend, string $accent, string $description): array
    {
        return [
            'id' => $id,
            'type' => 'stat',
            'title' => $title,
            'value' => $value,
            'change' => $change,
            'trend' => $trend,
            'accent' => $accent,
            'description' => $description,
        ];
    }

    /**
     * @param array<int, string> $labels
     * @param array<int, array{label: string, data: array<int, int>}> $datasets
     * @return array<string, mixed>
     */
    private function chart(string $id, string $title, string $chartType, array $labels, array $datasets): array
    {
        return [
            'id' => $id,
            'type' => 'chart',
            'title' => $title,
            'chartType' => $chartType,
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * @param array<int, array{id: string, primaryText: string, secondaryText: string, status: string}> $items
     * @return array<string, mixed>
     */
    private function list(string $id, string $title, array $items): array
    {
        return [
            'id' => $id,
            'type' => 'list',
            'title' => $title,
            'items' => $items,
        ];
    }

    /** @return array<string, mixed> */
    private function gauge(string $id, string $title, int $value, int $max, string $unit, string $accent): array
    {
        return [
            'id' => $id,
            'type' => 'gauge',
            'title' => $title,
            'value' => $value,
            'max' => $max,
            'unit' => $unit,
            'accent' => $accent,
        ];
    }
}
