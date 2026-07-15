<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;use SkyFi\Shared\Http\Request;use SkyFi\Shared\Http\Response;use SkyFi\Vendors\Services\VendorDashboardService;
final class VendorDashboardController
{
 public function __construct(private readonly VendorDashboardService $service,private readonly RequirePermissionMiddleware $auth){}public function show(Request $r):Response{$a=(int)($r->attributes()['claims']['sub']??0);$this->auth->authorize($a,'vendors.view');return new Response(200,['data'=>$this->service->dashboard()]);}
}
