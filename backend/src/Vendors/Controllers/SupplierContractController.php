<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;use SkyFi\Shared\Http\ApiResponse;use SkyFi\Shared\Http\Request;use SkyFi\Shared\Http\Response;use SkyFi\Vendors\DTOs\ContractData;use SkyFi\Vendors\DTOs\ContractListFilters;use SkyFi\Vendors\Services\SupplierContractService;
final class SupplierContractController
{
 public function __construct(private readonly SupplierContractService $service,private readonly RequirePermissionMiddleware $auth){}
 public function index(Request $r):Response{$this->can($r,'vendors.view');return $this->collection($this->service->list(ContractListFilters::fromQuery($r->query())));}public function supplierIndex(Request $r):Response{$this->can($r,'vendors.view');return $this->collection($this->service->list(ContractListFilters::fromQuery($r->query(),$this->vendor($r))));}
 public function show(Request $r):Response{$this->can($r,'vendors.view');$x=$this->service->get($this->vendor($r),$this->id($r));return ApiResponse::resource('supplier-contracts',(string)$x->id(),$x->toArray());}
 public function store(Request $r):Response{$a=$this->can($r,'vendors.contracts');$x=$this->service->create($this->vendor($r),ContractData::fromArray($r->body()),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('supplier-contracts',(string)$x->id(),$x->toArray(),201);}
 public function update(Request $r):Response{$a=$this->can($r,'vendors.contracts');$x=$this->service->update($this->vendor($r),$this->id($r),ContractData::fromArray($r->body()),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('supplier-contracts',(string)$x->id(),$x->toArray());}
 public function destroy(Request $r):Response{$a=$this->can($r,'vendors.contracts');$this->service->delete($this->vendor($r),$this->id($r),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::noContent();}
 private function can(Request $r,string $p):int{$a=(int)($r->attributes()['claims']['sub']??0);$this->auth->authorize($a,$p);return $a;}private function vendor(Request $r):int{return (int)($r->attributes()['route_params']['id']??0);}private function id(Request $r):int{return (int)($r->attributes()['route_params']['contractId']??0);}
 /** @param array<string,mixed> $x */private function collection(array $x):Response{return new Response(200,['data'=>array_map(static fn($i):array=>['type'=>'supplier-contracts','id'=>(string)$i->id(),'attributes'=>$i->toArray()],$x['items']),'meta'=>['current_page'=>$x['page'],'per_page'=>$x['perPage'],'total'=>$x['total'],'last_page'=>$x['lastPage']]]);}
}
