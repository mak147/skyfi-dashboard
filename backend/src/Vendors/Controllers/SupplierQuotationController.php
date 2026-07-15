<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;use SkyFi\Shared\Http\ApiResponse;use SkyFi\Shared\Http\Request;use SkyFi\Shared\Http\Response;use SkyFi\Vendors\DTOs\QuotationData;use SkyFi\Vendors\DTOs\QuotationListFilters;use SkyFi\Vendors\Services\SupplierQuotationService;
final class SupplierQuotationController
{
 public function __construct(private readonly SupplierQuotationService $service,private readonly RequirePermissionMiddleware $auth){}
 public function index(Request $r):Response{$this->can($r,'vendors.view');return $this->collection($this->service->list(QuotationListFilters::fromQuery($r->query())));}public function supplierIndex(Request $r):Response{$this->can($r,'vendors.view');return $this->collection($this->service->list(QuotationListFilters::fromQuery($r->query(),$this->vendor($r))));}
 public function comparison(Request $r):Response{$this->can($r,'vendors.view');$q=$r->query();return new Response(200,['data'=>$this->service->compare((string)($q['rfq_reference']??''),isset($q['product_id'])?(int)$q['product_id']:null)]);}
 public function show(Request $r):Response{$this->can($r,'vendors.view');$x=$this->service->get($this->vendor($r),$this->id($r));return ApiResponse::resource('supplier-quotations',(string)$x->id(),$x->toArray());}
 public function store(Request $r):Response{$a=$this->can($r,'vendors.create');$x=$this->service->create($this->vendor($r),QuotationData::fromArray($r->body()),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('supplier-quotations',(string)$x->id(),$x->toArray(),201);}
 public function update(Request $r):Response{$a=$this->can($r,'vendors.update');$x=$this->service->update($this->vendor($r),$this->id($r),QuotationData::fromArray($r->body()),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('supplier-quotations',(string)$x->id(),$x->toArray());}
 public function destroy(Request $r):Response{$a=$this->can($r,'vendors.delete');$this->service->delete($this->vendor($r),$this->id($r),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::noContent();}
 public function history(Request $r):Response{$this->can($r,'vendors.view');return new Response(200,['data'=>$this->service->history($this->vendor($r),$this->id($r))]);}
 private function can(Request $r,string $p):int{$a=(int)($r->attributes()['claims']['sub']??0);$this->auth->authorize($a,$p);return $a;}private function vendor(Request $r):int{return (int)($r->attributes()['route_params']['id']??0);}private function id(Request $r):int{return (int)($r->attributes()['route_params']['quotationId']??0);}
 /** @param array<string,mixed> $x */private function collection(array $x):Response{return new Response(200,['data'=>array_map(static fn($i):array=>['type'=>'supplier-quotations','id'=>(string)$i->id(),'attributes'=>$i->toArray()],$x['items']),'meta'=>['current_page'=>$x['page'],'per_page'=>$x['perPage'],'total'=>$x['total'],'last_page'=>$x['lastPage']]]);}
}
