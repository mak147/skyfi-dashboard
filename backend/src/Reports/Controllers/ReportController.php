<?php

declare(strict_types=1);

namespace SkyFi\Reports\Controllers;

use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Reports\Contracts\ReportRepositoryContract;
use SkyFi\Reports\DTOs\ReportRequest;
use SkyFi\Reports\Services\ReportCatalog;
use SkyFi\Reports\Services\ReportDashboardService;
use SkyFi\Reports\Services\ReportService;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class ReportController
{
    public function __construct(private readonly ReportService $service,private readonly ReportDashboardService $dashboards,private readonly ReportCatalog $catalog,private readonly ReportRepositoryContract $repository,private readonly RequirePermissionMiddleware $permissions){}
    public function catalog(Request$r):Response{$this->allow($r,'reports.view');return new Response(200,['data'=>$this->catalog->grouped()]);}
    public function filters(Request$r):Response{$this->allow($r,'reports.view');return new Response(200,['data'=>$this->repository->filterOptions()]);}
    public function generate(Request$r):Response{$this->allow($r,'reports.view');$result=$this->service->generate(ReportRequest::fromArray($r->body()));return new Response(200,['data'=>$result['report'],'meta'=>$result['meta']]);}
    public function dashboard(Request$r):Response{$this->allow($r,'analytics.view');$p=$r->attributes()['route_params']??[];$filters=$r->query()['filter']??[];return new Response(200,['data'=>$this->dashboards->get((string)($p['dashboard']??''),is_array($filters)?$filters:[])]);}
    private function allow(Request$r,string$p):int{$id=(int)(($r->attributes()['claims']['sub']??0));$this->permissions->authorize($id,$p);return$id;}
}
