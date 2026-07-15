<?php declare(strict_types=1);
namespace SkyFi\System\Controllers;
use SkyFi\Shared\Http\{Request,Response};
use SkyFi\System\Services\{SystemConfigurationProvider,SystemDashboardService};
final class SystemDashboardController extends SystemBaseController { public function __construct(private readonly SystemDashboardService $dashboard, private readonly SystemConfigurationProvider $config, \SkyFi\Rbac\Middleware\RequirePermissionMiddleware $p){parent::__construct($p);} public function dashboard(Request $r): Response { $this->user($r,'system.view'); return new Response(200,['data'=>$this->dashboard->payload()]); } public function configuration(Request $r): Response { $this->user($r,'system.view'); return new Response(200,['data'=>$this->config->all()]); }}
