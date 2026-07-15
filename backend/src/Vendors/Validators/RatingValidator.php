<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Validators;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Vendors\DTOs\RatingData;
final class RatingValidator
{
    public function validate(RatingData $data):void
    {
        $errors=[];$date=static fn(string $value):bool=>preg_match('/^\d{4}-\d{2}-\d{2}$/',$value)===1&&strtotime($value)!==false;
        if(!$date($data->reviewPeriodStart))$errors[]=$this->error('review_period_start','Review start must use YYYY-MM-DD.');
        if(!$date($data->reviewPeriodEnd))$errors[]=$this->error('review_period_end','Review end must use YYYY-MM-DD.');
        if($date($data->reviewPeriodStart)&&$date($data->reviewPeriodEnd)&&$data->reviewPeriodEnd<$data->reviewPeriodStart)$errors[]=$this->error('review_period_end','Review end cannot be before review start.');
        if($data->productQualityScore<0||$data->productQualityScore>5)$errors[]=$this->error('product_quality_score','Product quality score must be between 0 and 5.');
        if(preg_match('/^[A-Z]{3}$/',$data->currency)!==1)$errors[]=$this->error('currency','Currency must be a three-letter code.');
        if($errors!==[])throw new ValidationException($errors);
    }
    /** @return array<string,mixed> */private function error(string $field,string $detail):array{return ['code'=>'validation_error','detail'=>$detail,'source'=>['pointer'=>'/data/attributes/'.$field]];}
}
