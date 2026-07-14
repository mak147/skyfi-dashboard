<?php

declare(strict_types=1);

namespace SkyFi\Shared\Providers;

use PDO;
use SkyFi\Shared\Auth\Controllers\AuthController;
use SkyFi\Shared\Auth\Repositories\PdoRefreshTokenRepository;
use SkyFi\Shared\Auth\Repositories\PdoUserRepository;
use SkyFi\Shared\Auth\Services\AuthService;
use SkyFi\Shared\Auth\Services\JwtTokenService;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Logging\JsonLogger;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Rbac\Repositories\PdoRbacRepository;
use SkyFi\Rbac\Repositories\PdoAuditLogger;
use SkyFi\Rbac\Services\RbacService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Rbac\Controllers\RbacController;

final class Container
{
    /** @var array<string, object> */
    private array $instances = [];

    /** @param array<string, mixed> $config */
    public function __construct(array $config, array $databaseConfig)
    {
        $pdo = new PDO(
            (string) $databaseConfig['dsn'],
            (string) $databaseConfig['username'],
            (string) $databaseConfig['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );

        $this->instances[PDO::class] = $pdo;
        $this->instances[JsonLogger::class] = new JsonLogger(
            dirname(__DIR__, 3) . '/' . (getenv('LOG_PATH') ?: 'storage/logs/app.log'),
        );
        $this->instances[JwtTokenService::class] = new JwtTokenService(
            (string) $config['jwt_secret'],
            (string) $config['issuer'],
            (string) $config['audience'],
            (int) $config['jwt_access_ttl'],
        );
        $this->instances[PdoUserRepository::class] = new PdoUserRepository($pdo);
        $this->instances[PdoRefreshTokenRepository::class] = new PdoRefreshTokenRepository($pdo);
        $this->instances[AuthService::class] = new AuthService(
            $this->instances[PdoUserRepository::class],
            $this->instances[PdoRefreshTokenRepository::class],
            $this->instances[JwtTokenService::class],
            (int) $config['jwt_refresh_ttl'],
            (int) $config['jwt_session_refresh_ttl'],
        );
        $this->instances[AuthController::class] = new AuthController(
            $this->instances[AuthService::class],
            (string) $config['refresh_cookie_name'],
            (string) $config['refresh_cookie_path'],
            (bool) $config['refresh_cookie_secure'],
        );
        $this->instances[JwtAuthMiddleware::class] = new JwtAuthMiddleware($this->instances[JwtTokenService::class]);
        
        $this->instances[PdoRbacRepository::class] = new PdoRbacRepository($pdo);
        $this->instances[PdoAuditLogger::class] = new PdoAuditLogger($pdo);
        $this->instances[RbacService::class] = new RbacService(
            $this->instances[PdoRbacRepository::class],
            $this->instances[PdoAuditLogger::class]
        );
        $this->instances[RequirePermissionMiddleware::class] = new RequirePermissionMiddleware($this->instances[PdoRbacRepository::class]);
        $this->instances[RbacController::class] = new RbacController(
            $this->instances[RbacService::class],
            $this->instances[RequirePermissionMiddleware::class]
        );

        $this->instances[Router::class] = new Router();
    }

    /** @template T of object @param class-string<T> $id @return T */
    public function get(string $id): object
    {
        if (!isset($this->instances[$id])) {
            throw new \RuntimeException(sprintf('No service registered for %s.', $id));
        }

        return $this->instances[$id];
    }
}
