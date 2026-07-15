<?php declare(strict_types=1);
namespace SkyFi\System\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
abstract class SystemBaseController { public function __construct(protected readonly RequirePermissionMiddleware $permissions){} protected function user(Request $r,string $permission): int { $id=(int)($r->attributes()['claims']['sub']??0); $this->permissions->authorize($id,$permission); return $id; } protected function id(Request $r): int { $p=$r->attributes()['route_params']??[]; return (int)($p['id']??0); } }
