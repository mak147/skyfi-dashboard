<?php

declare(strict_types=1);

namespace SkyFi\Reports\Controllers;

use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Reports\ExportServices\ReportExportService;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Http\{ApiResponse,Request,Response};

final class ReportExportController
{
    public function __construct(private readonly ReportExportService $service,private readonly RequirePermissionMiddleware $permissions){}
    public function index(Request$r):Response{$u=$this->allow($r,'reports.export');return new Response(200,['data'=>$this->service->history($u)]);}
    public function store(Request$r):Response{$u=$this->allow($r,'reports.export');return new Response(201,['data'=>$this->service->create($r->body(),$u)]);}
    public function show(Request$r):Response{$u=$this->allow($r,'reports.export');return new Response(200,['data'=>$this->service->item($this->id($r),$u)]);}
    public function download(Request$r):Response{$u=$this->allow($r,'reports.export');$item=$this->service->item($this->id($r),$u);$path=(string)($item['file_path']??'');if($item['status']!=='completed'||!is_file($path))throw new NotFoundException('Export file is unavailable.');$body=file_get_contents($path);if($body===false)throw new NotFoundException('Export file is unavailable.');$name=preg_replace('/[^A-Za-z0-9._-]/','',(string)$item['file_name']);return(new Response(200,null,$body))->withHeaders(['Content-Type'=>(string)$item['mime_type'],'Content-Disposition'=>'attachment; filename="'.$name.'"']);}
    public function destroy(Request$r):Response{$u=$this->allow($r,'reports.manage');$this->service->delete($this->id($r),$u);return ApiResponse::noContent();}
    private function id(Request$r):int{return(int)($r->attributes()['route_params']['id']??0);}
    private function allow(Request$r,string$p):int{$u=(int)($r->attributes()['claims']['sub']??0);$this->permissions->authorize($u,$p);return$u;}
}
