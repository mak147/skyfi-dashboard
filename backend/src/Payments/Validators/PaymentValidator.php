<?php

declare(strict_types=1);
namespace SkyFi\Payments\Validators;
use SkyFi\Shared\Exceptions\ValidationException;
final class PaymentValidator
{
 private const STATUSES=['pending','completed','partially_applied','failed','cancelled','refunded'];
 /** @return array<string,mixed> */ public function validate(array $d,bool $receiving=false):array
 {
  $e=[];$out=[];
  foreach(['customer_id','payment_method_id','payment_account_id'] as $k){$v=(int)($d[$k]??0);if($v<1)$e[]=$this->err($k,'This field is required.');$out[$k]=$v;}
  $out['connection_id']=isset($d['connection_id'])&&(int)$d['connection_id']>0?(int)$d['connection_id']:null;
  foreach(['amount'=>false,'tax_amount'=>true,'discount_amount'=>true,'adjustment_amount'=>true] as $k=>$signed){$v=$d[$k]??($k==='amount'?null:0);if(!is_numeric($v)||(!$signed&&(float)$v<=0)||($signed&&$k!=='adjustment_amount'&&(float)$v<0)){$e[]=$this->err($k,$k==='amount'?'Amount must be greater than zero.':'Enter a valid amount.');$out[$k]='0.00';}else{$out[$k]=number_format((float)$v,2,'.','');}}
  $date=(string)($d['payment_date']??'');if($date===''||strtotime($date)===false)$e[]=$this->err('payment_date','Enter a valid payment date.');$out['payment_date']=$date;
  $status=(string)($d['status']??'pending');if(!in_array($status,self::STATUSES,true))$e[]=$this->err('status','Select a valid status.');if($receiving)$status='completed';$out['status']=$status;
  if(isset($d['reference_number'])&&strlen((string)$d['reference_number'])>150)$e[]=$this->err('reference_number','Reference number must be 150 characters or fewer.');
  if(isset($d['notes'])&&strlen((string)$d['notes'])>5000)$e[]=$this->err('notes','Notes must be 5000 characters or fewer.');
  if(isset($d['allocations'])&&!is_array($d['allocations']))$e[]=$this->err('allocations','Allocations must be a list.');
  if($e!==[])throw new ValidationException($e);return $out;
 }
 public function positiveAmount(mixed $v,string $path='amount'):string{if(!is_numeric($v)||(float)$v<=0)throw new ValidationException([$this->err($path,'Amount must be greater than zero.')]);return number_format((float)$v,2,'.','');}
 private function err(string $p,string $d):array{return ['code'=>'invalid','detail'=>$d,'source'=>['pointer'=>'/data/attributes/'.str_replace('.','/',$p)]];}
}
