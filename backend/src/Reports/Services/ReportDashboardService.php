<?php

declare(strict_types=1);

namespace SkyFi\Reports\Services;

use SkyFi\Reports\DTOs\ReportRequest;
use SkyFi\Shared\Exceptions\NotFoundException;

final class ReportDashboardService
{
    public function __construct(private readonly ReportService $reports) {}

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    public function get(string $dashboard,array $filters=[]):array
    {
        $map=[
            'executive'=>['customer.active','customer.growth','billing.revenue-by-month','billing.outstanding-invoices','finance.revenue-vs-expenses','support.ticket-volume','network.router-health'],
            'finance'=>['billing.revenue-by-month','billing.outstanding-invoices','billing.overdue-invoices','payment.monthly-collections','payment.methods','finance.cash-flow-summary','finance.revenue-vs-expenses'],
            'operations'=>['connection.active','customer.new-installations','field.installation-success-rate','field.technician-productivity','inventory.low-stock','purchasing.purchase-orders'],
            'network'=>['network.router-health','network.infrastructure-capacity','network.pppoe-sessions','network.hotspot-sessions','network.bandwidth-usage'],
            'support'=>['support.ticket-volume','support.sla-compliance','support.resolution-time'],
            'sales'=>['customer.growth','customer.new-installations','connection.package-distribution','connection.types','customer.churn'],
        ];
        $keys=$map[$dashboard]??throw new NotFoundException('Dashboard not found.');
        $widgets=[];
        foreach($keys as $key){$result=$this->reports->generate(new ReportRequest($key,$filters,1,12));$widgets[]=['report_key'=>$key,'title'=>$result['report']['name'],'kpis'=>$result['report']['kpis'],'visualizations'=>$result['report']['visualizations'],'rows'=>array_slice($result['report']['rows'],0,6),'drill_down'=>['path'=>'/reports/builder','report_key'=>$key]];}
        return ['key'=>$dashboard,'name'=>ucfirst($dashboard).' Dashboard','generated_at'=>(new \DateTimeImmutable())->format(DATE_ATOM),'widgets'=>$widgets];
    }
}
