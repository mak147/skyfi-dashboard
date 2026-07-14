<?php

declare(strict_types=1);

namespace SkyFi\Shared\Providers;

use PDO;
use SkyFi\Billing\Controllers\InvoiceController;
use SkyFi\Billing\Contracts\BillingScheduleRepositoryContract;
use SkyFi\Billing\Contracts\InvoiceRepositoryContract;
use SkyFi\Billing\Contracts\InvoiceServiceContract;
use SkyFi\Billing\Repositories\PdoBillingScheduleRepository;
use SkyFi\Billing\Repositories\PdoInvoiceRepository;
use SkyFi\Billing\Services\InvoiceService;
use SkyFi\Payments\Controllers\PaymentController;
use SkyFi\Payments\Repositories\PdoPaymentRepository;
use SkyFi\Payments\Services\PaymentService;
use SkyFi\Payments\Validators\PaymentValidator;
use SkyFi\Connections\Controllers\ConnectionController;
use SkyFi\Connections\Repositories\PdoConnectionRepository;
use SkyFi\Connections\Services\ConnectionService;
use SkyFi\Customers\Controllers\CustomerController;
use SkyFi\Customers\Repositories\PdoCustomerRepository;
use SkyFi\Customers\Services\CustomerService;
use SkyFi\Packages\Controllers\PackageController;
use SkyFi\Packages\Repositories\PdoPackageRepository;
use SkyFi\Packages\Services\PackageService;
use SkyFi\Dashboard\Controllers\DashboardController;
use SkyFi\Dashboard\Services\DashboardService;
use SkyFi\Shared\Auth\Controllers\AuthController;
use SkyFi\Shared\Auth\Repositories\PdoRefreshTokenRepository;
use SkyFi\Shared\Auth\Repositories\PdoUserRepository;
use SkyFi\Shared\Auth\Services\AuthService;
use SkyFi\Shared\Auth\Services\JwtTokenService;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Logging\JsonLogger;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Infrastructure\Repositories\PdoPopSiteRepository;
use SkyFi\Infrastructure\Repositories\PdoTowerRepository;
use SkyFi\Infrastructure\Repositories\PdoSectorRepository;
use SkyFi\Infrastructure\Repositories\PdoNetworkDeviceRepository;
use SkyFi\Infrastructure\Services\PopSiteService;
use SkyFi\Infrastructure\Services\TowerService;
use SkyFi\Infrastructure\Services\SectorService;
use SkyFi\Infrastructure\Services\NetworkDeviceService;
use SkyFi\Infrastructure\Services\InfrastructureDashboardService;
use SkyFi\Infrastructure\Validators\PopSiteValidator;
use SkyFi\Infrastructure\Validators\TowerValidator;
use SkyFi\Infrastructure\Validators\SectorValidator;
use SkyFi\Infrastructure\Validators\NetworkDeviceValidator;
use SkyFi\Infrastructure\Controllers\PopSiteController;
use SkyFi\Infrastructure\Controllers\TowerController;
use SkyFi\Infrastructure\Controllers\SectorController;
use SkyFi\Infrastructure\Controllers\NetworkDeviceController;
use SkyFi\Infrastructure\Controllers\InfrastructureDashboardController;
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

        $this->instances[PdoPaymentRepository::class] = new PdoPaymentRepository($pdo);
        $this->instances[DashboardService::class] = new DashboardService($this->instances[PdoPaymentRepository::class]);
        $this->instances[DashboardController::class] = new DashboardController($this->instances[DashboardService::class]);

        $this->instances[PdoCustomerRepository::class] = new PdoCustomerRepository($pdo);
        $this->instances[CustomerService::class] = new CustomerService(
            $this->instances[PdoCustomerRepository::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[CustomerController::class] = new CustomerController(
            $this->instances[CustomerService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[PdoPackageRepository::class] = new PdoPackageRepository($pdo);
        $this->instances[PackageService::class] = new PackageService(
            $this->instances[PdoPackageRepository::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[PackageController::class] = new PackageController(
            $this->instances[PackageService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[PdoConnectionRepository::class] = new PdoConnectionRepository($pdo);
        $this->instances[ConnectionService::class] = new ConnectionService(
            $this->instances[PdoConnectionRepository::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[ConnectionController::class] = new ConnectionController(
            $this->instances[ConnectionService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[PdoInvoiceRepository::class] = new PdoInvoiceRepository($pdo);
        $this->instances[PdoBillingScheduleRepository::class] = new PdoBillingScheduleRepository($pdo);
        $this->instances[InvoiceService::class] = new InvoiceService(
            $this->instances[PdoInvoiceRepository::class],
            $this->instances[PdoBillingScheduleRepository::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[InvoiceController::class] = new InvoiceController(
            $this->instances[InvoiceService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[PaymentValidator::class] = new PaymentValidator();
        $this->instances[PaymentService::class] = new PaymentService(
            $this->instances[PdoPaymentRepository::class],
            $this->instances[PaymentValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[PaymentController::class] = new PaymentController(
            $this->instances[PaymentService::class],
            $this->instances[PaymentValidator::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[\SkyFi\Finance\Repositories\PdoFinanceRepository::class] = new \SkyFi\Finance\Repositories\PdoFinanceRepository($pdo);
        $this->instances[\SkyFi\Finance\Contracts\FinanceRepositoryContract::class] = clone $this->instances[\SkyFi\Finance\Repositories\PdoFinanceRepository::class];
        $this->instances[\SkyFi\Finance\Services\FinanceService::class] = new \SkyFi\Finance\Services\FinanceService(
            $this->instances[\SkyFi\Finance\Repositories\PdoFinanceRepository::class]
        );
        $this->instances[\SkyFi\Finance\Controllers\FinanceController::class] = new \SkyFi\Finance\Controllers\FinanceController(
            $this->instances[\SkyFi\Finance\Services\FinanceService::class]
        );

        /** @var array<string, mixed> $mikrotikConfig */
        $mikrotikConfig = $config['mikrotik'];
        $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterRepository::class] = new \SkyFi\Mikrotik\Repositories\PdoRouterRepository($pdo);
        $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterGroupRepository::class] = new \SkyFi\Mikrotik\Repositories\PdoRouterGroupRepository($pdo);
        $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterTagRepository::class] = new \SkyFi\Mikrotik\Repositories\PdoRouterTagRepository($pdo);
        $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterHealthRepository::class] = new \SkyFi\Mikrotik\Repositories\PdoRouterHealthRepository($pdo);
        $this->instances[\SkyFi\Mikrotik\Validators\RouterValidator::class] = new \SkyFi\Mikrotik\Validators\RouterValidator();
        $this->instances[\SkyFi\Mikrotik\Services\CredentialCipher::class] = new \SkyFi\Mikrotik\Services\CredentialCipher(
            (string) $mikrotikConfig['credential_encryption_key'],
        );
        $this->instances[\SkyFi\Mikrotik\Services\MikrotikConnectionPool::class] = new \SkyFi\Mikrotik\Services\MikrotikConnectionPool(
            (int) $mikrotikConfig['connect_timeout_seconds'],
            (int) $mikrotikConfig['command_timeout_seconds'],
            (int) $mikrotikConfig['max_retries'],
            (bool) $mikrotikConfig['tls_verify_peer'],
            is_string($mikrotikConfig['tls_ca_file']) ? $mikrotikConfig['tls_ca_file'] : null,
            (int) $mikrotikConfig['max_connections_per_router'],
        );
        $this->instances[\SkyFi\Mikrotik\Services\RouterOsApiClient::class] = new \SkyFi\Mikrotik\Services\RouterOsApiClient(
            $this->instances[\SkyFi\Mikrotik\Services\MikrotikConnectionPool::class],
        );
        $this->instances[\SkyFi\Mikrotik\Services\RouterService::class] = new \SkyFi\Mikrotik\Services\RouterService(
            $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterRepository::class],
            $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterGroupRepository::class],
            $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterTagRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\CredentialCipher::class],
            $this->instances[\SkyFi\Mikrotik\Validators\RouterValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Mikrotik\Services\RouterTaxonomyService::class] = new \SkyFi\Mikrotik\Services\RouterTaxonomyService(
            $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterGroupRepository::class],
            $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterTagRepository::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Mikrotik\Services\RouterDiscoveryService::class] = new \SkyFi\Mikrotik\Services\RouterDiscoveryService(
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterOsApiClient::class],
            $this->instances[\SkyFi\Mikrotik\Validators\RouterValidator::class],
        );
        $this->instances[\SkyFi\Mikrotik\Services\RouterHealthService::class] = new \SkyFi\Mikrotik\Services\RouterHealthService(
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterRepository::class],
            $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterHealthRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterOsApiClient::class],
        );
        $this->instances[\SkyFi\Mikrotik\Controllers\RouterController::class] = new \SkyFi\Mikrotik\Controllers\RouterController(
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterDiscoveryService::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterHealthService::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterTaxonomyService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeAccountRepository::class] = new \SkyFi\Pppoe\Repositories\PdoPppoeAccountRepository($pdo);
        $this->instances[\SkyFi\Pppoe\Contracts\PppoeAccountRepositoryContract::class] = $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeAccountRepository::class];

        $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeSessionRepository::class] = new \SkyFi\Pppoe\Repositories\PdoPppoeSessionRepository($pdo);
        $this->instances[\SkyFi\Pppoe\Contracts\PppoeSessionRepositoryContract::class] = $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeSessionRepository::class];

        $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeSyncLogger::class] = new \SkyFi\Pppoe\Repositories\PdoPppoeSyncLogger($pdo);
        $this->instances[\SkyFi\Pppoe\Contracts\PppoeSyncLoggerContract::class] = $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeSyncLogger::class];

        $this->instances[\SkyFi\Pppoe\Validators\PppoeValidator::class] = new \SkyFi\Pppoe\Validators\PppoeValidator();

        $this->instances[\SkyFi\Pppoe\Services\PppoeService::class] = new \SkyFi\Pppoe\Services\PppoeService(
            $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeAccountRepository::class],
            $this->instances[\SkyFi\Customers\Repositories\PdoCustomerRepository::class],
            $this->instances[\SkyFi\Connections\Repositories\PdoConnectionRepository::class],
            $this->instances[\SkyFi\Packages\Repositories\PdoPackageRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Services\MikrotikConnectionPool::class],
            $this->instances[\SkyFi\Mikrotik\Services\CredentialCipher::class],
            $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeSyncLogger::class],
            $this->instances[\SkyFi\Pppoe\Validators\PppoeValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Pppoe\Contracts\PppoeServiceContract::class] = $this->instances[\SkyFi\Pppoe\Services\PppoeService::class];

        $this->instances[\SkyFi\Pppoe\Services\PppoeSessionMonitorService::class] = new \SkyFi\Pppoe\Services\PppoeSessionMonitorService(
            $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeAccountRepository::class],
            $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeSessionRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Services\MikrotikConnectionPool::class],
            $this->instances[PdoAuditLogger::class],
        );

        $this->instances[\SkyFi\Pppoe\Services\PppoeSyncService::class] = new \SkyFi\Pppoe\Services\PppoeSyncService(
            $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeAccountRepository::class],
            $this->instances[\SkyFi\Customers\Repositories\PdoCustomerRepository::class],
            $this->instances[\SkyFi\Connections\Repositories\PdoConnectionRepository::class],
            $this->instances[\SkyFi\Packages\Repositories\PdoPackageRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Services\MikrotikConnectionPool::class],
            $this->instances[\SkyFi\Mikrotik\Services\CredentialCipher::class],
            $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeSyncLogger::class],
        );

        $this->instances[\SkyFi\Pppoe\Controllers\PppoeAccountController::class] = new \SkyFi\Pppoe\Controllers\PppoeAccountController(
            $this->instances[\SkyFi\Pppoe\Services\PppoeService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[\SkyFi\Pppoe\Controllers\PppoeSessionController::class] = new \SkyFi\Pppoe\Controllers\PppoeSessionController(
            $this->instances[\SkyFi\Pppoe\Services\PppoeSessionMonitorService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[\SkyFi\Pppoe\Controllers\PppoeSyncController::class] = new \SkyFi\Pppoe\Controllers\PppoeSyncController(
            $this->instances[\SkyFi\Pppoe\Services\PppoeSyncService::class],
            $this->instances[\SkyFi\Pppoe\Repositories\PdoPppoeSyncLogger::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        // ─── Hotspot Module ───────────────────────────────────────────────────
        $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotUserRepository::class] = new \SkyFi\Hotspot\Repositories\PdoHotspotUserRepository($pdo);
        $this->instances[\SkyFi\Hotspot\Contracts\HotspotUserRepositoryContract::class] = $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotUserRepository::class];

        $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotProfileRepository::class] = new \SkyFi\Hotspot\Repositories\PdoHotspotProfileRepository($pdo);
        $this->instances[\SkyFi\Hotspot\Contracts\HotspotProfileRepositoryContract::class] = $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotProfileRepository::class];

        $this->instances[\SkyFi\Hotspot\Repositories\PdoVoucherRepository::class] = new \SkyFi\Hotspot\Repositories\PdoVoucherRepository($pdo);
        $this->instances[\SkyFi\Hotspot\Contracts\VoucherRepositoryContract::class] = $this->instances[\SkyFi\Hotspot\Repositories\PdoVoucherRepository::class];

        $this->instances[\SkyFi\Hotspot\Repositories\PdoVoucherBatchRepository::class] = new \SkyFi\Hotspot\Repositories\PdoVoucherBatchRepository($pdo);
        $this->instances[\SkyFi\Hotspot\Contracts\VoucherBatchRepositoryContract::class] = $this->instances[\SkyFi\Hotspot\Repositories\PdoVoucherBatchRepository::class];

        $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotSessionRepository::class] = new \SkyFi\Hotspot\Repositories\PdoHotspotSessionRepository($pdo);
        $this->instances[\SkyFi\Hotspot\Contracts\HotspotSessionRepositoryContract::class] = $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotSessionRepository::class];

        $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotSyncLogger::class] = new \SkyFi\Hotspot\Repositories\PdoHotspotSyncLogger($pdo);
        $this->instances[\SkyFi\Hotspot\Contracts\HotspotSyncLoggerContract::class] = $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotSyncLogger::class];

        $this->instances[\SkyFi\Hotspot\Validators\HotspotUserValidator::class] = new \SkyFi\Hotspot\Validators\HotspotUserValidator();
        $this->instances[\SkyFi\Hotspot\Validators\HotspotProfileValidator::class] = new \SkyFi\Hotspot\Validators\HotspotProfileValidator();
        $this->instances[\SkyFi\Hotspot\Validators\VoucherValidator::class] = new \SkyFi\Hotspot\Validators\VoucherValidator();

        $this->instances[\SkyFi\Hotspot\Services\HotspotUserService::class] = new \SkyFi\Hotspot\Services\HotspotUserService(
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotUserRepository::class],
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotProfileRepository::class],
            $this->instances[\SkyFi\Customers\Repositories\PdoCustomerRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Services\MikrotikConnectionPool::class],
            $this->instances[\SkyFi\Mikrotik\Services\CredentialCipher::class],
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotSyncLogger::class],
            $this->instances[\SkyFi\Hotspot\Validators\HotspotUserValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Hotspot\Contracts\HotspotUserServiceContract::class] = $this->instances[\SkyFi\Hotspot\Services\HotspotUserService::class];

        $this->instances[\SkyFi\Hotspot\Services\HotspotProfileService::class] = new \SkyFi\Hotspot\Services\HotspotProfileService(
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotProfileRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Services\MikrotikConnectionPool::class],
            $this->instances[\SkyFi\Hotspot\Validators\HotspotProfileValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Hotspot\Contracts\HotspotProfileServiceContract::class] = $this->instances[\SkyFi\Hotspot\Services\HotspotProfileService::class];

        $this->instances[\SkyFi\Hotspot\Services\VoucherService::class] = new \SkyFi\Hotspot\Services\VoucherService(
            $this->instances[\SkyFi\Hotspot\Repositories\PdoVoucherRepository::class],
            $this->instances[\SkyFi\Hotspot\Repositories\PdoVoucherBatchRepository::class],
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotUserRepository::class],
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotProfileRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Services\MikrotikConnectionPool::class],
            $this->instances[\SkyFi\Mikrotik\Services\CredentialCipher::class],
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotSyncLogger::class],
            $this->instances[\SkyFi\Hotspot\Validators\VoucherValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Hotspot\Contracts\VoucherServiceContract::class] = $this->instances[\SkyFi\Hotspot\Services\VoucherService::class];

        $this->instances[\SkyFi\Hotspot\Services\HotspotSessionMonitorService::class] = new \SkyFi\Hotspot\Services\HotspotSessionMonitorService(
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotUserRepository::class],
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotSessionRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Services\MikrotikConnectionPool::class],
            $this->instances[PdoAuditLogger::class],
        );

        $this->instances[\SkyFi\Hotspot\Services\HotspotSyncService::class] = new \SkyFi\Hotspot\Services\HotspotSyncService(
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotUserRepository::class],
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotProfileRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Services\MikrotikConnectionPool::class],
            $this->instances[\SkyFi\Mikrotik\Services\CredentialCipher::class],
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotSyncLogger::class],
        );

        $this->instances[\SkyFi\Hotspot\Controllers\HotspotUserController::class] = new \SkyFi\Hotspot\Controllers\HotspotUserController(
            $this->instances[\SkyFi\Hotspot\Services\HotspotUserService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[\SkyFi\Hotspot\Controllers\HotspotProfileController::class] = new \SkyFi\Hotspot\Controllers\HotspotProfileController(
            $this->instances[\SkyFi\Hotspot\Services\HotspotProfileService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[\SkyFi\Hotspot\Controllers\VoucherController::class] = new \SkyFi\Hotspot\Controllers\VoucherController(
            $this->instances[\SkyFi\Hotspot\Services\VoucherService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[\SkyFi\Hotspot\Controllers\HotspotSessionController::class] = new \SkyFi\Hotspot\Controllers\HotspotSessionController(
            $this->instances[\SkyFi\Hotspot\Services\HotspotSessionMonitorService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[\SkyFi\Hotspot\Controllers\HotspotSyncController::class] = new \SkyFi\Hotspot\Controllers\HotspotSyncController(
            $this->instances[\SkyFi\Hotspot\Services\HotspotSyncService::class],
            $this->instances[\SkyFi\Hotspot\Repositories\PdoHotspotSyncLogger::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        // ─── End Hotspot Module ───────────────────────────────────────────────

        // ─── Infrastructure Module ──────────────────────────────────────
        $this->instances[PdoPopSiteRepository::class] = new PdoPopSiteRepository($pdo);
        $this->instances[PdoTowerRepository::class] = new PdoTowerRepository($pdo);
        $this->instances[PdoSectorRepository::class] = new PdoSectorRepository($pdo);
        $this->instances[PdoNetworkDeviceRepository::class] = new PdoNetworkDeviceRepository($pdo);

        $this->instances[PopSiteValidator::class] = new PopSiteValidator();
        $this->instances[TowerValidator::class] = new TowerValidator();
        $this->instances[SectorValidator::class] = new SectorValidator();
        $this->instances[NetworkDeviceValidator::class] = new NetworkDeviceValidator();

        $this->instances[PopSiteService::class] = new PopSiteService(
            $this->instances[PdoPopSiteRepository::class],
            $this->instances[PopSiteValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[PopSiteServiceContract::class] = $this->instances[PopSiteService::class];

        $this->instances[TowerService::class] = new TowerService(
            $this->instances[PdoTowerRepository::class],
            $this->instances[PdoPopSiteRepository::class],
            $this->instances[TowerValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[TowerServiceContract::class] = $this->instances[TowerService::class];

        $this->instances[SectorService::class] = new SectorService(
            $this->instances[PdoSectorRepository::class],
            $this->instances[PdoTowerRepository::class],
            $this->instances[PdoNetworkDeviceRepository::class],
            $this->instances[SectorValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[SectorServiceContract::class] = $this->instances[SectorService::class];

        $this->instances[NetworkDeviceService::class] = new NetworkDeviceService(
            $this->instances[PdoNetworkDeviceRepository::class],
            $this->instances[PdoPopSiteRepository::class],
            $this->instances[PdoTowerRepository::class],
            $this->instances[\SkyFi\Mikrotik\Repositories\PdoRouterRepository::class],
            $this->instances[\SkyFi\Mikrotik\Services\CredentialCipher::class],
            $this->instances[NetworkDeviceValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[NetworkDeviceServiceContract::class] = $this->instances[NetworkDeviceService::class];

        $this->instances[InfrastructureDashboardService::class] = new InfrastructureDashboardService($pdo);
        $this->instances[InfrastructureDashboardContract::class] = $this->instances[InfrastructureDashboardService::class];

        $this->instances[PopSiteController::class] = new PopSiteController(
            $this->instances[PopSiteService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[TowerController::class] = new TowerController(
            $this->instances[TowerService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[SectorController::class] = new SectorController(
            $this->instances[SectorService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[NetworkDeviceController::class] = new NetworkDeviceController(
            $this->instances[NetworkDeviceService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[InfrastructureDashboardController::class] = new InfrastructureDashboardController(
            $this->instances[InfrastructureDashboardService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        // ─── End Infrastructure Module ──────────────────────────────────

        $this->instances[Router::class] = new Router();

        // Register Finance Event Listeners
        \SkyFi\Shared\Events\EventDispatcher::listen('invoice.generated', function($invoice) {
            // Debit Accounts Receivable, Credit Revenue
            // Need Accounts. For simplicity, we hardcode COA lookup or let FinanceService handle it.
            $finance = $this->get(\SkyFi\Finance\Services\FinanceService::class);
            $finance->createJournalEntry([
                'description' => 'Invoice Generated: ' . $invoice['invoice_number'],
                'transaction_date' => date('Y-m-d'),
                'source_id' => $invoice['id'],
                'source_type' => 'App\Models\Invoice'
            ], [
                // Assuming Account 1200 is A/R, Account 4000 is Revenue
                ['account_id' => 2, 'debit_amount' => $invoice['total_amount'], 'credit_amount' => null],
                ['account_id' => 4, 'debit_amount' => null, 'credit_amount' => $invoice['total_amount']]
            ], 1); // Admin user
        });

        \SkyFi\Shared\Events\EventDispatcher::listen('payment.completed', function($payment) {
            $finance = $this->get(\SkyFi\Finance\Services\FinanceService::class);
            $finance->createJournalEntry([
                'description' => 'Payment Received: ' . $payment['payment_number'],
                'transaction_date' => date('Y-m-d'),
                'source_id' => $payment['id'],
                'source_type' => 'App\Models\Payment'
            ], [
                // Assuming Account 1000 is Cash/Bank, Account 1200 is A/R
                ['account_id' => 1, 'debit_amount' => $payment['amount'], 'credit_amount' => null],
                ['account_id' => 2, 'debit_amount' => null, 'credit_amount' => $payment['amount']]
            ], 1);
        });

        \SkyFi\Shared\Events\EventDispatcher::listen('payment.reversed', function($payment) {
            $finance = $this->get(\SkyFi\Finance\Services\FinanceService::class);
            $finance->createJournalEntry([
                'description' => 'Payment Reversed: ' . $payment['payment_number'],
                'transaction_date' => date('Y-m-d'),
                'source_id' => $payment['id'],
                'source_type' => 'App\Models\Payment'
            ], [
                // Reverse of Payment Received
                ['account_id' => 2, 'debit_amount' => $payment['amount'], 'credit_amount' => null],
                ['account_id' => 1, 'debit_amount' => null, 'credit_amount' => $payment['amount']]
            ], 1);
        });
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
