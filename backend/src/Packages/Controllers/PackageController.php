<?php

declare(strict_types=1);
namespace SkyFi\Packages\Controllers;
use SkyFi\Packages\Contracts\PackageServiceContract;
use SkyFi\Packages\Data\{BulkPackageActionData,CreatePackageData,DuplicatePackageData,PackageListFilters,UpdatePackageData};
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\{ApiResponse,Request,Response};
final class PackageController
{
 public function __construct(private readonly PackageServiceContract $service,private readonly RequirePermissionMiddleware $auth) {}
 public function index(Request $r): Response {$this->allow($r,'packages.view');$x=$this->service->list(PackageListFilters::fromQuery($r->query()));$data=array_map(fn($p)=>['type'=>'packages','id'=>(string)$p->id(),'attributes'=>$p->toArray()],$x['items']);return new Response(200,['data'=>$data,'meta'=>['current_page'=>$x['page'],'per_page'=>$x['perPage'],'total'=>$x['total'],'last_page'=>$x['lastPage']]]);}
 public function show(Request $r): Response {$this->allow($r,'packages.view');$p=$this->service->get($this->id($r));return ApiResponse::resource('packages',(string)$p->id(),$p->toArray());}
 public function store(Request $r): Response {$u=$this->allow($r,'packages.create');$p=$this->service->create(CreatePackageData::fromArray($r->body()),$u,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('packages',(string)$p->id(),$p->toArray(),201);}
 public function update(Request $r): Response {$u=$this->allow($r,'packages.update');$p=$this->service->update($this->id($r),UpdatePackageData::fromArray($r->body()),$u,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('packages',(string)$p->id(),$p->toArray());}
 public function destroy(Request $r): Response {$u=$this->allow($r,'packages.delete');$this->service->delete($this->id($r),$u,$r->ipAddress(),$r->userAgent());return ApiResponse::noContent();}
 public function status(Request $r): Response {$u=$this->allow($r,'packages.manage');$status=is_string($r->body()['status']??null)?$r->body()['status']:'';$p=$this->service->changeStatus($this->id($r),$status,$u,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('packages',(string)$p->id(),$p->toArray());}
 public function duplicate(Request $r): Response {$u=$this->allow($r,'packages.create');$p=$this->service->duplicate($this->id($r),DuplicatePackageData::fromArray($r->body()),$u,$r->ipAddress(),$r->userAgent());return ApiResponse::resource('packages',(string)$p->id(),$p->toArray(),201);}
 public function bulkStatus(Request $r): Response {$u=$this->allow($r,'packages.manage');return new Response(200,['data'=>$this->service->bulkStatus(BulkPackageActionData::fromArray($r->body(),true),$u,$r->ipAddress(),$r->userAgent())]);}
 public function bulkDelete(Request $r): Response {$u=$this->allow($r,'packages.delete');return new Response(200,['data'=>$this->service->bulkDelete(BulkPackageActionData::fromArray($r->body()),$u,$r->ipAddress(),$r->userAgent())]);}
 public function statistics(Request $r): Response {$this->allow($r,'packages.view');return new Response(200,['data'=>$this->service->statistics()]);}
 public function activity(Request $r): Response {$this->allow($r,'packages.view');return new Response(200,['data'=>$this->service->activity($this->id($r))]);}
 public function export(Request $r): Response {$this->allow($r,'packages.export');$q=$r->query();$page=1;$rows=[['Code','Name','Category','Status','Monthly Price','Download Kbps','Upload Kbps']];do{$q['page']=['number'=>$page,'size'=>100];$x=$this->service->list(PackageListFilters::fromQuery($q));foreach($x['items'] as $p){$a=$p->toArray();$rows[]=[(string)$a['code'],(string)$a['name'],(string)$a['category_name'],(string)$a['status'],(string)($a['monthly_price']??''),(string)($a['download_kbps']??''),(string)($a['upload_kbps']??'')];}$page++;}while($page<=$x['lastPage']);return Response::downloadCsv($rows,'skyfi-packages.csv');}
 private function allow(Request $r,string $p): int {$u=(int)($r->attributes()['claims']['sub']??0);$this->auth->authorize($u,$p);return $u;}
 private function id(Request $r): int {return (int)($r->attributes()['route_params']['id']??0);}
}
