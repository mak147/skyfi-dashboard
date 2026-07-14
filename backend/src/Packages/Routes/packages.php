<?php

declare(strict_types=1);
use SkyFi\Packages\Controllers\PackageController;
use SkyFi\Shared\Http\{Request,Router};
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Providers\Container;
return static function(Router $router,Container $container): void {$c=$container->get(PackageController::class);$auth=$container->get(JwtAuthMiddleware::class);$protect=static fn(callable $h):callable=>static function(Request $r)use($h,$auth){$a=$r->attributes();$a['claims']=$auth->authenticate($r);return $h($r->withAttributes($a));};
 $router->add('GET','/api/v1/packages',$protect($c->index(...)));$router->add('POST','/api/v1/packages',$protect($c->store(...)));$router->add('GET','/api/v1/packages/statistics',$protect($c->statistics(...)));$router->add('GET','/api/v1/packages/export',$protect($c->export(...)));$router->add('PATCH','/api/v1/packages/bulk/status',$protect($c->bulkStatus(...)));$router->add('DELETE','/api/v1/packages/bulk',$protect($c->bulkDelete(...)));$router->add('GET','/api/v1/packages/{id}',$protect($c->show(...)));$router->add('PUT','/api/v1/packages/{id}',$protect($c->update(...)));$router->add('DELETE','/api/v1/packages/{id}',$protect($c->destroy(...)));$router->add('PATCH','/api/v1/packages/{id}/status',$protect($c->status(...)));$router->add('POST','/api/v1/packages/{id}/duplicate',$protect($c->duplicate(...)));$router->add('GET','/api/v1/packages/{id}/activity',$protect($c->activity(...)));
};
