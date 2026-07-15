<?php
declare(strict_types=1);
namespace SkyFi\FieldService\DTOs;
final class InstallationRequestData
{
    public function __construct(public readonly int $customerId,public readonly int $connectionId,public readonly string $priority,public readonly string $source,public readonly string $serviceAddress,public readonly ?string $preferredStartAt,public readonly ?string $preferredEndAt,public readonly ?float $latitude,public readonly ?float $longitude,public readonly ?string $notes) {}
    public static function fromArray(array $d): self { $text=static fn(string $k):?string=>trim((string)($d[$k]??''))?:null; return new self((int)($d['customer_id']??0),(int)($d['connection_id']??0),(string)($d['priority']??'normal'),(string)($d['source']??'manual'),(string)($d['service_address']??''),$text('preferred_start_at'),$text('preferred_end_at'),isset($d['latitude'])?(float)$d['latitude']:null,isset($d['longitude'])?(float)$d['longitude']:null,$text('notes')); }
    public function toArray(): array { return ['customer_id'=>$this->customerId,'connection_id'=>$this->connectionId,'priority'=>$this->priority,'source'=>$this->source,'service_address'=>$this->serviceAddress,'preferred_start_at'=>$this->preferredStartAt,'preferred_end_at'=>$this->preferredEndAt,'latitude'=>$this->latitude,'longitude'=>$this->longitude,'notes'=>$this->notes]; }
}
