<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;use SkyFi\Shared\Http\ApiResponse;use SkyFi\Shared\Http\Request;use SkyFi\Shared\Http\Response;use SkyFi\Vendors\DTOs\RatingData;use SkyFi\Vendors\Services\SupplierPerformanceService;
final class SupplierPerformanceController
{
 public function __construct(private readonly SupplierPerformanceService $service,private readonly RequirePermissionMiddleware $auth){}
 public function performance(Request $r):Response{$this->can($r,'vendors.view');return new Response(200,['data'=>$this->service->performance($this->vendor($r))]);}
 public function ratings(Request $r):Response{$this->can($r,'vendors.view');return new Response(200,['data'=>array_map(static fn($x):array=>['type'=>'supplier-ratings','id'=>(string)$x->id(),'attributes'=>$x->toArray()],$this->service->ratings($this->vendor($r)))]);}
 public function store(Request $r):Response{$a=$this->can($r,'vendors.manage');$x=$this->service->create($this->vendor($r),RatingData::fromArray($r->body()),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('supplier-ratings',(string)$x->id(),$x->toArray(),201);}
 public function update(Request $r):Response{$a=$this->can($r,'vendors.manage');$x=$this->service->update($this->vendor($r),$this->id($r),RatingData::fromArray($r->body()),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('supplier-ratings',(string)$x->id(),$x->toArray());}
 public function destroy(Request $r):Response{$a=$this->can($r,'vendors.manage');$this->service->delete($this->vendor($r),$this->id($r),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::noContent();}
 private function can(Request $r,string $p):int{$a=(int)($r->attributes()['claims']['sub']??0);$this->auth->authorize($a,$p);return $a;}private function vendor(Request $r):int{return (int)($r->attributes()['route_params']['id']??0);}private function id(Request $r):int{return (int)($r->attributes()['route_params']['ratingId']??0);}
}
