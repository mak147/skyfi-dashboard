<?php declare(strict_types=1);
namespace SkyFi\System\Controllers;
use SkyFi\Shared\Http\{ApiResponse,Request,Response};
use SkyFi\System\Services\SystemAdministrationService;
final class LocalizationController extends SystemBaseController { public function __construct(private readonly SystemAdministrationService $service, \SkyFi\Rbac\Middleware\RequirePermissionMiddleware $p){parent::__construct($p);} public function show(Request $r): Response { $this->user($r,'system.view'); $d=$this->service->localization->first(); return ApiResponse::resource('localization',(string)$d['id'],$d); } public function update(Request $r): Response { $u=$this->user($r,'system.update'); $d=$this->service->updateLocalization($r->body(),$u,$r->ipAddress(),$r->userAgent()); return ApiResponse::resource('localization',(string)$d['id'],$d); } public function options(Request $r): Response { $this->user($r,'system.view'); return new Response(200,['data'=>$this->service->localization->options()]); }}
