<?php

declare(strict_types=1);

namespace SkyFi\Reports\Controllers;

use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Reports\DTOs\ReportRequest;
use SkyFi\Reports\Services\{ReportConfigurationService,ReportService};
use SkyFi\Shared\Http\{ApiResponse,Request,Response};

final class ReportConfigurationController
{
    public function __construct(private readonly ReportConfigurationService $service,private readonly ReportService $reports,private readonly RequirePermissionMiddleware $permissions){}
    public function saved(Request$r):Response{$u=$this->allow($r,'reports.view');return new Response(200,['data'=>$this->service->saved($u)]);}
    public function savedShow(Request$r):Response{$u=$this->allow($r,'reports.view');return new Response(200,['data'=>$this->service->savedOne($this->id($r),$u)]);}
    public function savedStore(Request$r):Response{$u=$this->allow($r,'reports.manage');return new Response(201,['data'=>$this->service->saveSaved($r->body(),$u)]);}
    public function savedUpdate(Request$r):Response{$u=$this->allow($r,'reports.manage');return new Response(200,['data'=>$this->service->saveSaved($r->body(),$u,$this->id($r))]);}
    public function savedDelete(Request$r):Response{$u=$this->allow($r,'reports.manage');$this->service->deleteSaved($this->id($r),$u);return ApiResponse::noContent();}
    public function savedRun(Request$r):Response{$u=$this->allow($r,'reports.view');$saved=$this->service->savedOne($this->id($r),$u);$body=$r->body();$result=$this->reports->generate(new ReportRequest((string)$saved['report_key'],is_array($saved['filters'])?$saved['filters']:[],max(1,(int)($body['page']??1)),min(100,max(1,(int)($body['per_page']??25)))));return new Response(200,['data'=>$result['report'],'meta'=>$result['meta']]);}
    public function templates(Request$r):Response{$this->allow($r,'reports.view');return new Response(200,['data'=>$this->service->templates()]);}
    public function templateShow(Request$r):Response{$this->allow($r,'reports.view');return new Response(200,['data'=>$this->service->template($this->id($r))]);}
    public function templateStore(Request$r):Response{$u=$this->allow($r,'reports.manage');return new Response(201,['data'=>$this->service->saveTemplate($r->body(),$u)]);}
    public function templateUpdate(Request$r):Response{$u=$this->allow($r,'reports.manage');return new Response(200,['data'=>$this->service->saveTemplate($r->body(),$u,$this->id($r))]);}
    public function templateDelete(Request$r):Response{$this->allow($r,'reports.manage');$this->service->deleteTemplate($this->id($r));return ApiResponse::noContent();}
    public function schedules(Request$r):Response{$u=$this->allow($r,'reports.manage');return new Response(200,['data'=>$this->service->schedules($u)]);}
    public function scheduleShow(Request$r):Response{$u=$this->allow($r,'reports.manage');return new Response(200,['data'=>$this->service->schedule($this->id($r),$u)]);}
    public function scheduleStore(Request$r):Response{$u=$this->allow($r,'reports.manage');return new Response(201,['data'=>$this->service->saveSchedule($r->body(),$u)]);}
    public function scheduleUpdate(Request$r):Response{$u=$this->allow($r,'reports.manage');return new Response(200,['data'=>$this->service->saveSchedule($r->body(),$u,$this->id($r))]);}
    public function scheduleDelete(Request$r):Response{$u=$this->allow($r,'reports.manage');$this->service->deleteSchedule($this->id($r),$u);return ApiResponse::noContent();}
    private function id(Request$r):int{return(int)(($r->attributes()['route_params']['id']??0));}
    private function allow(Request$r,string$p):int{$u=(int)($r->attributes()['claims']['sub']??0);$this->permissions->authorize($u,$p);return$u;}
}
