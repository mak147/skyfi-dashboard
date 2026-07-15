<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;use SkyFi\Shared\Http\ApiResponse;use SkyFi\Shared\Http\Request;use SkyFi\Shared\Http\Response;use SkyFi\Vendors\Services\SupplierService;
final class SupplierCategoryController
{
 public function __construct(private readonly SupplierService $service,private readonly RequirePermissionMiddleware $auth){}
 public function index(Request $r):Response{$this->can($r,'vendors.view');return new Response(200,['data'=>$this->service->categories(filter_var($r->query()['active_only']??false,FILTER_VALIDATE_BOOLEAN))]);}
 public function store(Request $r):Response{$a=$this->can($r,'vendors.manage');return new Response(201,['data'=>$this->service->createCategory($r->body(),$a,$r->ipAddress(),$r->userAgent())]);}
 public function update(Request $r):Response{$a=$this->can($r,'vendors.manage');return new Response(200,['data'=>$this->service->updateCategory($this->id($r),$r->body(),$a,$r->ipAddress(),$r->userAgent())]);}
 public function destroy(Request $r):Response{$a=$this->can($r,'vendors.manage');$this->service->deleteCategory($this->id($r),$a,$r->ipAddress(),$r->userAgent());return ApiResponse::noContent();}
 private function can(Request $r,string $p):int{$a=(int)($r->attributes()['claims']['sub']??0);$this->auth->authorize($a,$p);return $a;}private function id(Request $r):int{return (int)($r->attributes()['route_params']['categoryId']??0);}
}
