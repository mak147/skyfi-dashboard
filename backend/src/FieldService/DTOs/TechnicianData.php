<?php
declare(strict_types=1);
namespace SkyFi\FieldService\DTOs;
final class TechnicianData
{
    public function __construct(public readonly int $userId,public readonly ?int $teamId,public readonly string $employeeCode,public readonly ?string $phone,public readonly string $status,public readonly int $maxDailyJobs,public readonly ?string $notes) {}
    public static function fromArray(array $d):self {$t=static fn(string $k):?string=>trim((string)($d[$k]??''))?:null;return new self((int)($d['user_id']??0),isset($d['field_team_id'])?(int)$d['field_team_id']:null,(string)($d['employee_code']??''),$t('phone'),(string)($d['status']??'active'),(int)($d['max_daily_jobs']??6),$t('notes'));}
    public function toArray():array{return ['user_id'=>$this->userId,'field_team_id'=>$this->teamId,'employee_code'=>$this->employeeCode,'phone'=>$this->phone,'status'=>$this->status,'max_daily_jobs'=>$this->maxDailyJobs,'notes'=>$this->notes];}
}
