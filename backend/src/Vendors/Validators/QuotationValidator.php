<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Validators;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Vendors\DTOs\QuotationData;
final class QuotationValidator
{
    private const STATUSES=['draft','received','under_review','accepted','rejected','expired'];
    public function validate(QuotationData $data):void
    {
        $errors=[];$date=static fn(string $value):bool=>preg_match('/^\d{4}-\d{2}-\d{2}$/',$value)===1&&strtotime($value)!==false;
        if ($data->quotationNumber===''||strlen($data->quotationNumber)>100)$errors[]=$this->error('quotation_number','Quotation number is required and must be at most 100 characters.');
        if (!$date($data->quotationDate))$errors[]=$this->error('quotation_date','Quotation date must use YYYY-MM-DD.');
        if ($data->validUntil!==null&&!$date($data->validUntil))$errors[]=$this->error('valid_until','Valid-until date must use YYYY-MM-DD.');
        if ($data->validUntil!==null&&$date($data->quotationDate)&&$date($data->validUntil)&&$data->validUntil<$data->quotationDate)$errors[]=$this->error('valid_until','Validity cannot end before the quotation date.');
        if (preg_match('/^[A-Z]{3}$/',$data->currency)!==1)$errors[]=$this->error('currency','Currency must be a three-letter code.');
        if ($data->taxAmount<0)$errors[]=$this->error('tax_amount','Tax amount cannot be negative.');
        if (!in_array($data->status,self::STATUSES,true))$errors[]=$this->error('status','Quotation status is invalid.');
        if ($data->items===[])$errors[]=$this->error('items','At least one quotation item is required.');
        foreach($data->items as $i=>$item){if(trim((string)($item['description']??''))==='')$errors[]=$this->error("items/{$i}/description",'Item description is required.');if(!is_numeric($item['quantity']??null)||(float)$item['quantity']<=0)$errors[]=$this->error("items/{$i}/quantity",'Quantity must be greater than zero.');if(!is_numeric($item['unit_price']??null)||(float)$item['unit_price']<0)$errors[]=$this->error("items/{$i}/unit_price",'Unit price cannot be negative.');if(isset($item['lead_time_days'])&&$item['lead_time_days']!==''&&(int)$item['lead_time_days']<0)$errors[]=$this->error("items/{$i}/lead_time_days",'Lead time cannot be negative.');}
        if($errors!==[])throw new ValidationException($errors);
    }
    /** @return array<string,mixed> */private function error(string $field,string $detail):array{return ['code'=>'validation_error','detail'=>$detail,'source'=>['pointer'=>'/data/attributes/'.$field]];}
}
