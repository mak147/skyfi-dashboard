<?php declare(strict_types=1);
namespace SkyFi\System\Controllers;
use SkyFi\Shared\Http\{ApiResponse,Request,Response};
use SkyFi\System\Services\SystemAdministrationService;
final class NotificationSettingsController extends SystemBaseController { public function __construct(private readonly SystemAdministrationService $service, \SkyFi\Rbac\Middleware\RequirePermissionMiddleware $p){parent::__construct($p);} public function show(Request $r): Response { $this->user($r,'system.view'); $d=$this->service->notifications->first(); return ApiResponse::resource('notification-preferences',(string)$d['id'],$d); } public function update(Request $r): Response { $u=$this->user($r,'system.update'); $d=$this->service->updateNotifications($r->body(),$u,$r->ipAddress(),$r->userAgent()); return ApiResponse::resource('notification-preferences',(string)$d['id'],$d); }}
