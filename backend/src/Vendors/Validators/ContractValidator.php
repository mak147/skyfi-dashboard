<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Validators;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Vendors\DTOs\ContractData;
final class ContractValidator
{
    private const STATUSES=['draft','active','expired','terminated','renewed'];
    public function validate(ContractData $data):void
    {
        $errors=[];$date=static fn(string $value):bool=>preg_match('/^\d{4}-\d{2}-\d{2}$/',$value)===1 && strtotime($value)!==false;
        if ($data->contractNumber===''||strlen($data->contractNumber)>80)$errors[]=$this->error('contract_number','Contract number is required and must be at most 80 characters.');
        if (!$date($data->startDate))$errors[]=$this->error('start_date','Start date must use YYYY-MM-DD.');
        if (!$date($data->endDate))$errors[]=$this->error('end_date','End date must use YYYY-MM-DD.');
        if ($date($data->startDate)&&$date($data->endDate)&&$data->endDate<$data->startDate)$errors[]=$this->error('end_date','End date cannot be before start date.');
        if ($data->renewalDate!==null&&!$date($data->renewalDate))$errors[]=$this->error('renewal_date','Renewal date must use YYYY-MM-DD.');
        if ($data->contractValue<0)$errors[]=$this->error('contract_value','Contract value cannot be negative.');
        if (preg_match('/^[A-Z]{3}$/',$data->currency)!==1)$errors[]=$this->error('currency','Currency must be a three-letter code.');
        if (!in_array($data->status,self::STATUSES,true))$errors[]=$this->error('status','Contract status is invalid.');
        if ($errors!==[])throw new ValidationException($errors);
    }
    /** @return array<string,mixed> */private function error(string $field,string $detail):array{return ['code'=>'validation_error','detail'=>$detail,'source'=>['pointer'=>'/data/attributes/'.$field]];}
}
