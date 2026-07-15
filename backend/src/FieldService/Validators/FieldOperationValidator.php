<?php
declare(strict_types=1);
namespace SkyFi\FieldService\Validators;
use SkyFi\Shared\Exceptions\ValidationException;
final class FieldOperationValidator
{
    public function checkIn(array $visit):void {if(($visit['status']??'')!=='scheduled')$this->fail('Only a scheduled visit can be checked in.');}
    public function checkOut(array $visit):void {if(($visit['status']??'')!=='checked_in')$this->fail('Check in before checking out.');}
    public function completion(array $order,array $materials,array $visits):void {$e=[];if(($order['assigned_technician_id']??null)===null)$e[]=$this->error('assigned_technician_id','Assign a technician before completion.');if(!in_array($order['status']??'',['in_progress','on_hold'],true))$e[]=$this->error('status','Only active field work can be completed.');if($visits!==[]&&!array_filter($visits,fn(array $v)=>$v['status']==='checked_out'))$e[]=$this->error('visits','At least one visit must be checked out.');foreach($materials as $m){if((float)$m['quantity_used']>0&&$m['asset_id']===null&&$m['warehouse_location_id']===null)$e[]=$this->error('materials','Quantity materials require a warehouse location.');}if($e)throw new ValidationException($e);}
    private function fail(string $d):never{throw new ValidationException([$this->error('field_visit',$d)]);} private function error(string $f,string $d):array{return ['code'=>'validation_error','detail'=>$d,'source'=>['pointer'=>'/data/attributes/'.$f]];}
}
