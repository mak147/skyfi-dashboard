<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;use SkyFi\Shared\Http\ApiResponse;use SkyFi\Shared\Http\Request;use SkyFi\Shared\Http\Response;use SkyFi\Vendors\DTOs\SupplierData;use SkyFi\Vendors\DTOs\SupplierListFilters;use SkyFi\Vendors\Services\SupplierService;
final class SupplierController
{
 public function __construct(private readonly SupplierService $service,private readonly RequirePermissionMiddleware $auth){}
 public function index(Request $r):Response{$this->can($r,'vendors.view');$x=$this->service->list(SupplierListFilters::fromQuery($r->query()));return $this->collection($x);}
 public function show(Request $r):Response{$this->can($r,'vendors.view');$x=$this->service->get($this->id($r));return ApiResponse::resource('suppliers',(string)$x->id(),$x->toArray());}
 public function store(Request $r):Response{$a=$this->can($r,'vendors.create');$x=$this->service->create(SupplierData::fromArray($r->body()),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('suppliers',(string)$x->id(),$x->toArray(),201);}
 public function update(Request $r):Response{$a=$this->can($r,'vendors.update');$x=$this->service->update($this->id($r),SupplierData::fromArray($r->body()),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('suppliers',(string)$x->id(),$x->toArray());}
 public function destroy(Request $r):Response{$a=$this->can($r,'vendors.delete');$x=$this->service->archive($this->id($r),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('suppliers',(string)$x->id(),$x->toArray());}
 public function activate(Request $r):Response{$a=$this->can($r,'vendors.manage');$x=$this->service->activate($this->id($r),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('suppliers',(string)$x->id(),$x->toArray());}
 public function status(Request $r):Response{$a=$this->can($r,'vendors.manage');$x=$this->service->changeStatus($this->id($r),(string)($r->body()['status']??''),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('suppliers',(string)$x->id(),$x->toArray());}
 public function purchaseOrders(Request $r):Response{$this->can($r,'vendors.view');return new Response(200,['data'=>$this->service->purchaseOrders($this->id($r))]);}public function products(Request $r):Response{$this->can($r,'vendors.view');return new Response(200,['data'=>$this->service->products($this->id($r))]);}public function financialReferences(Request $r):Response{$this->can($r,'vendors.view');return new Response(200,['data'=>$this->service->financialReferences($this->id($r))]);}
 private function can(Request $r,string $p):int{$a=(int)($r->attributes()['claims']['sub']??0);$this->auth->authorize($a,$p);return $a;}private function id(Request $r):int{return (int)($r->attributes()['route_params']['id']??0);}
 /** @param array<string,mixed> $x */private function collection(array $x):Response{return new Response(200,['data'=>array_map(static fn($i):array=>['type'=>'suppliers','id'=>(string)$i->id(),'attributes'=>$i->toArray()],$x['items']),'meta'=>['current_page'=>$x['page'],'per_page'=>$x['perPage'],'total'=>$x['total'],'last_page'=>$x['lastPage']]]);}
}
