<?php
declare(strict_types=1);
namespace SkyFi\FieldService\Controllers;
use SkyFi\FieldService\Services\FieldOperationService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\{Request,Response,ApiResponse};
final class FieldOperationController
{
 public function __construct(private readonly FieldOperationService$service,private readonly RequirePermissionMiddleware$auth){}
 public function index(Request$r):Response{$this->can($r,'field.view');$type=$this->type($r);$data=$type==='materials'?$this->service->materials($this->wo($r)):($type==='visits'?$this->service->visits($this->wo($r)):$this->service->logs($this->wo($r)));return new Response(200,['data'=>$data]);}
 public function store(Request$r):Response{$a=$this->can($r,'field.update');$type=$this->type($r);$x=$type==='materials'?$this->service->saveMaterial($this->wo($r),null,$r->body(),$a):($type==='visits'?$this->service->createVisit($this->wo($r),$r->body(),$a):$this->service->saveLog($this->wo($r),null,$r->body(),$a));return new Response(201,['data'=>$x]);}
 public function update(Request$r):Response{$a=$this->can($r,'field.update');$type=$this->type($r);$id=$this->item($r);$x=$type==='materials'?$this->service->saveMaterial($this->wo($r),$id,$r->body(),$a):$this->service->saveLog($this->wo($r),$id,$r->body(),$a);return new Response(200,['data'=>$x]);}
 public function destroy(Request$r):Response{$a=$this->can($r,'field.update');$this->type($r)==='materials'?$this->service->deleteMaterial($this->wo($r),$this->item($r),$a):$this->service->deleteLog($this->wo($r),$this->item($r),$a);return ApiResponse::noContent();}
 public function visitAction(Request$r):Response{$a=$this->can($r,'field.update');$x=$this->service->visitAction($this->wo($r),$this->item($r),(string)($r->attributes()['route_params']['action']??''),$r->body(),$a);return new Response(200,['data'=>$x]);}
 private function can(Request$r,string$p):int{$a=(int)($r->attributes()['claims']['sub']??0);$this->auth->authorize($a,$p);return$a;}private function wo(Request$r):int{return(int)($r->attributes()['route_params']['id']??0);}private function item(Request$r):int{return(int)($r->attributes()['route_params']['itemId']??0);}private function type(Request$r):string{return(string)($r->attributes()['route_params']['resource']??'');}
}
