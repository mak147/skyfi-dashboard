<?php
declare(strict_types=1);
namespace SkyFi\FieldService\DTOs;
final class WorkOrderData
{
    public function __construct(public readonly array $values) {}
    public static function fromArray(array $d): self
    {
        $ids=['installation_request_id','customer_id','connection_id','support_ticket_id','monitoring_alert_id','pop_site_id','tower_id','network_device_id','assigned_technician_id','field_team_id'];
        $out=[]; foreach($ids as $k){$out[$k]=isset($d[$k])&&$d[$k]!==''?(int)$d[$k]:null;}
        foreach(['type','priority','title','service_address','scheduled_start_at','scheduled_end_at','notes'] as $k){$out[$k]=isset($d[$k])&&trim((string)$d[$k])!==''?trim((string)$d[$k]):null;}
        $out['estimated_duration_minutes']=(int)($d['estimated_duration_minutes']??120); $out['installation_charge']=(float)($d['installation_charge']??0);
        return new self($out);
    }
}
