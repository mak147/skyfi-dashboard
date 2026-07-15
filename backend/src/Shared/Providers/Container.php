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
use SkyFi\Shared\Auth\Repositories\PdoPasswordResetRepository;
use SkyFi\Shared\Auth\Repositories\PdoRefreshTokenRepository;
use SkyFi\Shared\Auth\Repositories\PdoUserRepository;
use SkyFi\Shared\Auth\Services\AuthService;
use SkyFi\Shared\Auth\Services\JwtTokenService;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Logging\JsonLogger;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\RateLimitMiddleware;
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
use SkyFi\Audit\Repositories\PdoAuditLogRepository;
use SkyFi\Audit\Repositories\PdoActivityRepository;
use SkyFi\Audit\Repositories\PdoComplianceRepository;
use SkyFi\Audit\Repositories\PdoRetentionRepository;
use SkyFi\Audit\Repositories\PdoAuditExportRepository;
use SkyFi\Audit\Services\AuditService;
use SkyFi\Audit\Services\AuditExportService;
use SkyFi\Audit\Services\ComplianceService;
use SkyFi\Audit\Services\AuditEventSubscriber;
use SkyFi\Audit\Validators\AuditValidator;
use SkyFi\Audit\Controllers\AuditLogController;
use SkyFi\Audit\Controllers\ActivityController;
use SkyFi\Audit\Controllers\AuditExportController;
use SkyFi\Audit\Controllers\ComplianceController;
use SkyFi\Audit\Contracts\AuditLogRepositoryContract;
use SkyFi\Audit\Contracts\ActivityRepositoryContract;
use SkyFi\Audit\Contracts\ComplianceRepositoryContract;
use SkyFi\Audit\Contracts\RetentionRepositoryContract;
use SkyFi\Audit\Contracts\AuditExportRepositoryContract;
use SkyFi\Audit\Contracts\AuditServiceContract;
use SkyFi\Audit\Contracts\ComplianceServiceContract;

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
        $this->instances[PdoPasswordResetRepository::class] = new PdoPasswordResetRepository($pdo);
        $this->instances[AuthService::class] = new AuthService(
            $this->instances[PdoUserRepository::class],
            $this->instances[PdoRefreshTokenRepository::class],
            $this->instances[PdoPasswordResetRepository::class],
            $this->instances[JwtTokenService::class],
            $pdo,
            (int) $config['jwt_refresh_ttl'],
            (int) $config['jwt_session_refresh_ttl'],
        );
        $this->instances[AuthController::class] = new AuthController(
            $this->instances[AuthService::class],
            (string) $config['refresh_cookie_name'],
            (string) $config['refresh_cookie_path'],
            (bool) $config['refresh_cookie_secure'],
            (bool) $config['expose_password_reset_token'],
        );
        $this->instances[JwtAuthMiddleware::class] = new JwtAuthMiddleware($this->instances[JwtTokenService::class]);
        $this->instances[RateLimitMiddleware::class] = new RateLimitMiddleware($pdo, 20, 60);
        
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

        $this->instances[\SkyFi\System\Repositories\PdoCompanyRepository::class] = new \SkyFi\System\Repositories\PdoCompanyRepository($pdo);
        $this->instances[\SkyFi\System\Repositories\PdoBranchRepository::class] = new \SkyFi\System\Repositories\PdoBranchRepository($pdo);
        $this->instances[\SkyFi\System\Repositories\PdoDepartmentRepository::class] = new \SkyFi\System\Repositories\PdoDepartmentRepository($pdo);
        $this->instances[\SkyFi\System\Repositories\PdoSystemSettingsRepository::class] = new \SkyFi\System\Repositories\PdoSystemSettingsRepository($pdo);
        $this->instances[\SkyFi\System\Repositories\PdoBrandingRepository::class] = new \SkyFi\System\Repositories\PdoBrandingRepository($pdo);
        $this->instances[\SkyFi\System\Repositories\PdoLocalizationRepository::class] = new \SkyFi\System\Repositories\PdoLocalizationRepository($pdo);
        $this->instances[\SkyFi\System\Repositories\PdoNotificationPreferenceRepository::class] = new \SkyFi\System\Repositories\PdoNotificationPreferenceRepository($pdo);
        $this->instances[\SkyFi\System\Validators\SystemValidator::class] = new \SkyFi\System\Validators\SystemValidator();
        $this->instances[\SkyFi\System\Services\SystemAdministrationService::class] = new \SkyFi\System\Services\SystemAdministrationService(
            $this->instances[\SkyFi\System\Repositories\PdoCompanyRepository::class],
            $this->instances[\SkyFi\System\Repositories\PdoBranchRepository::class],
            $this->instances[\SkyFi\System\Repositories\PdoDepartmentRepository::class],
            $this->instances[\SkyFi\System\Repositories\PdoSystemSettingsRepository::class],
            $this->instances[\SkyFi\System\Repositories\PdoBrandingRepository::class],
            $this->instances[\SkyFi\System\Repositories\PdoLocalizationRepository::class],
            $this->instances[\SkyFi\System\Repositories\PdoNotificationPreferenceRepository::class],
            $this->instances[\SkyFi\System\Validators\SystemValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\System\Services\SystemConfigurationProvider::class] = new \SkyFi\System\Services\SystemConfigurationProvider(
            $this->instances[\SkyFi\System\Repositories\PdoCompanyRepository::class],
            $this->instances[\SkyFi\System\Repositories\PdoSystemSettingsRepository::class],
            $this->instances[\SkyFi\System\Repositories\PdoBrandingRepository::class],
            $this->instances[\SkyFi\System\Repositories\PdoLocalizationRepository::class],
            $this->instances[\SkyFi\System\Repositories\PdoNotificationPreferenceRepository::class],
        );
        $this->instances[\SkyFi\System\Services\BrandingAssetService::class] = new \SkyFi\System\Services\BrandingAssetService();
        $this->instances[\SkyFi\System\Services\SystemDashboardService::class] = new \SkyFi\System\Services\SystemDashboardService($this->instances[\SkyFi\System\Repositories\PdoSystemSettingsRepository::class], $this->instances[\SkyFi\System\Services\SystemConfigurationProvider::class]);
        $this->instances[\SkyFi\System\Controllers\CompanySettingsController::class] = new \SkyFi\System\Controllers\CompanySettingsController($this->instances[\SkyFi\System\Services\SystemAdministrationService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\System\Controllers\BranchController::class] = new \SkyFi\System\Controllers\BranchController($this->instances[\SkyFi\System\Services\SystemAdministrationService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\System\Controllers\DepartmentController::class] = new \SkyFi\System\Controllers\DepartmentController($this->instances[\SkyFi\System\Services\SystemAdministrationService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\System\Controllers\SystemSettingsController::class] = new \SkyFi\System\Controllers\SystemSettingsController($this->instances[\SkyFi\System\Services\SystemAdministrationService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\System\Controllers\BrandingController::class] = new \SkyFi\System\Controllers\BrandingController($this->instances[\SkyFi\System\Services\SystemAdministrationService::class], $this->instances[\SkyFi\System\Services\BrandingAssetService::class], $this->instances[\SkyFi\System\Services\SystemConfigurationProvider::class], $this->instances[\SkyFi\System\Validators\SystemValidator::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\System\Controllers\LocalizationController::class] = new \SkyFi\System\Controllers\LocalizationController($this->instances[\SkyFi\System\Services\SystemAdministrationService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\System\Controllers\NotificationSettingsController::class] = new \SkyFi\System\Controllers\NotificationSettingsController($this->instances[\SkyFi\System\Services\SystemAdministrationService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\System\Controllers\SystemDashboardController::class] = new \SkyFi\System\Controllers\SystemDashboardController($this->instances[\SkyFi\System\Services\SystemDashboardService::class], $this->instances[\SkyFi\System\Services\SystemConfigurationProvider::class], $this->instances[RequirePermissionMiddleware::class]);

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
        $this->instances[BillingScheduleRepositoryContract::class] = $this->instances[PdoBillingScheduleRepository::class];
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
        $this->instances[\SkyFi\Finance\Contracts\FinanceRepositoryContract::class] = $this->instances[\SkyFi\Finance\Repositories\PdoFinanceRepository::class];
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

        // ─── Monitoring & Observability Module ──────────────────────────
        $this->instances[\SkyFi\Monitoring\Repositories\PdoEventLoggingRepository::class] = new \SkyFi\Monitoring\Repositories\PdoEventLoggingRepository($pdo);
        $this->instances[\SkyFi\Monitoring\Contracts\EventLoggingRepositoryContract::class] = $this->instances[\SkyFi\Monitoring\Repositories\PdoEventLoggingRepository::class];

        $this->instances[\SkyFi\Monitoring\Repositories\PdoDeviceStatusRepository::class] = new \SkyFi\Monitoring\Repositories\PdoDeviceStatusRepository($pdo);
        $this->instances[\SkyFi\Monitoring\Contracts\DeviceStatusRepositoryContract::class] = $this->instances[\SkyFi\Monitoring\Repositories\PdoDeviceStatusRepository::class];

        $this->instances[\SkyFi\Monitoring\Repositories\PdoInterfaceSnapshotRepository::class] = new \SkyFi\Monitoring\Repositories\PdoInterfaceSnapshotRepository($pdo);
        $this->instances[\SkyFi\Monitoring\Contracts\InterfaceSnapshotRepositoryContract::class] = $this->instances[\SkyFi\Monitoring\Repositories\PdoInterfaceSnapshotRepository::class];

        $this->instances[\SkyFi\Monitoring\Repositories\PdoAlertRepository::class] = new \SkyFi\Monitoring\Repositories\PdoAlertRepository($pdo);
        $this->instances[\SkyFi\Monitoring\Contracts\AlertRepositoryContract::class] = $this->instances[\SkyFi\Monitoring\Repositories\PdoAlertRepository::class];

        $this->instances[\SkyFi\Monitoring\Repositories\PdoSyncEventRepository::class] = new \SkyFi\Monitoring\Repositories\PdoSyncEventRepository($pdo);
        $this->instances[\SkyFi\Monitoring\Contracts\SyncEventRepositoryContract::class] = $this->instances[\SkyFi\Monitoring\Repositories\PdoSyncEventRepository::class];

        $this->instances[\SkyFi\Monitoring\Validators\AlertValidator::class] = new \SkyFi\Monitoring\Validators\AlertValidator();
        $this->instances[\SkyFi\Monitoring\Validators\MonitoringValidator::class] = new \SkyFi\Monitoring\Validators\MonitoringValidator();

        $this->instances[\SkyFi\Monitoring\Services\EventLoggingService::class] = new \SkyFi\Monitoring\Services\EventLoggingService(
            $this->instances[\SkyFi\Monitoring\Repositories\PdoEventLoggingRepository::class],
            $this->instances[\SkyFi\Monitoring\Repositories\PdoSyncEventRepository::class],
            $this->instances[\SkyFi\Monitoring\Validators\MonitoringValidator::class],
        );
        $this->instances[\SkyFi\Monitoring\Contracts\EventLoggingServiceContract::class] = $this->instances[\SkyFi\Monitoring\Services\EventLoggingService::class];

        $this->instances[\SkyFi\Monitoring\Services\AlertManagementService::class] = new \SkyFi\Monitoring\Services\AlertManagementService(
            $this->instances[\SkyFi\Monitoring\Repositories\PdoAlertRepository::class],
            $this->instances[\SkyFi\Monitoring\Services\EventLoggingService::class],
            $this->instances[\SkyFi\Monitoring\Validators\AlertValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Monitoring\Contracts\AlertManagementServiceContract::class] = $this->instances[\SkyFi\Monitoring\Services\AlertManagementService::class];

        $this->instances[\SkyFi\Monitoring\Services\DeviceHealthPollingService::class] = new \SkyFi\Monitoring\Services\DeviceHealthPollingService(
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterHealthService::class],
            $this->instances[\SkyFi\Mikrotik\Services\MikrotikConnectionPool::class],
            $this->instances[NetworkDeviceService::class],
            $this->instances[\SkyFi\Monitoring\Repositories\PdoDeviceStatusRepository::class],
            $this->instances[\SkyFi\Monitoring\Repositories\PdoInterfaceSnapshotRepository::class],
            $this->instances[\SkyFi\Monitoring\Services\AlertManagementService::class],
            $this->instances[\SkyFi\Monitoring\Services\EventLoggingService::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Monitoring\Contracts\DeviceHealthPollingServiceContract::class] = $this->instances[\SkyFi\Monitoring\Services\DeviceHealthPollingService::class];

        $this->instances[\SkyFi\Monitoring\Services\MonitoringDashboardService::class] = new \SkyFi\Monitoring\Services\MonitoringDashboardService(
            $this->instances[\SkyFi\Mikrotik\Services\RouterService::class],
            $this->instances[\SkyFi\Mikrotik\Services\RouterHealthService::class],
            $this->instances[NetworkDeviceService::class],
            $this->instances[\SkyFi\Monitoring\Repositories\PdoAlertRepository::class],
            $this->instances[\SkyFi\Monitoring\Repositories\PdoInterfaceSnapshotRepository::class],
            $this->instances[\SkyFi\Monitoring\Repositories\PdoDeviceStatusRepository::class],
            $this->instances[\SkyFi\Pppoe\Services\PppoeSessionMonitorService::class],
            $this->instances[\SkyFi\Hotspot\Services\HotspotSessionMonitorService::class],
        );
        $this->instances[\SkyFi\Monitoring\Contracts\MonitoringDashboardServiceContract::class] = $this->instances[\SkyFi\Monitoring\Services\MonitoringDashboardService::class];

        $this->instances[\SkyFi\Monitoring\Controllers\MonitoringDashboardController::class] = new \SkyFi\Monitoring\Controllers\MonitoringDashboardController(
            $this->instances[\SkyFi\Monitoring\Services\MonitoringDashboardService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[\SkyFi\Monitoring\Controllers\DeviceHealthController::class] = new \SkyFi\Monitoring\Controllers\DeviceHealthController(
            $this->instances[\SkyFi\Monitoring\Services\MonitoringDashboardService::class],
            $this->instances[\SkyFi\Monitoring\Services\DeviceHealthPollingService::class],
            $this->instances[\SkyFi\Monitoring\Repositories\PdoInterfaceSnapshotRepository::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[\SkyFi\Monitoring\Controllers\AlertController::class] = new \SkyFi\Monitoring\Controllers\AlertController(
            $this->instances[\SkyFi\Monitoring\Services\AlertManagementService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );

        $this->instances[\SkyFi\Monitoring\Controllers\EventLogController::class] = new \SkyFi\Monitoring\Controllers\EventLogController(
            $this->instances[\SkyFi\Monitoring\Services\EventLoggingService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        // ─── End Monitoring & Observability Module ──────────────────────

        // ─── Support Ticket & Helpdesk Module ───────────────────────────
        $this->instances[\SkyFi\Support\Repositories\PdoTicketRepository::class] = new \SkyFi\Support\Repositories\PdoTicketRepository($pdo);
        $this->instances[\SkyFi\Support\Contracts\TicketRepositoryContract::class] = $this->instances[\SkyFi\Support\Repositories\PdoTicketRepository::class];
        $this->instances[\SkyFi\Support\Repositories\PdoTicketCommentRepository::class] = new \SkyFi\Support\Repositories\PdoTicketCommentRepository($pdo);
        $this->instances[\SkyFi\Support\Contracts\TicketCommentRepositoryContract::class] = $this->instances[\SkyFi\Support\Repositories\PdoTicketCommentRepository::class];
        $this->instances[\SkyFi\Support\Repositories\PdoTicketAssignmentRepository::class] = new \SkyFi\Support\Repositories\PdoTicketAssignmentRepository($pdo);
        $this->instances[\SkyFi\Support\Contracts\TicketAssignmentRepositoryContract::class] = $this->instances[\SkyFi\Support\Repositories\PdoTicketAssignmentRepository::class];
        $this->instances[\SkyFi\Support\Validators\TicketValidator::class] = new \SkyFi\Support\Validators\TicketValidator();
        $this->instances[\SkyFi\Support\Validators\TicketWorkflowValidator::class] = new \SkyFi\Support\Validators\TicketWorkflowValidator();
        $this->instances[\SkyFi\Support\Services\TicketService::class] = new \SkyFi\Support\Services\TicketService(
            $this->instances[\SkyFi\Support\Repositories\PdoTicketRepository::class],
            $this->instances[\SkyFi\Support\Repositories\PdoTicketCommentRepository::class],
            $this->instances[\SkyFi\Support\Repositories\PdoTicketAssignmentRepository::class],
            $this->instances[\SkyFi\Support\Validators\TicketValidator::class],
            $this->instances[\SkyFi\Support\Validators\TicketWorkflowValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Support\Contracts\TicketServiceContract::class] = $this->instances[\SkyFi\Support\Services\TicketService::class];
        $this->instances[\SkyFi\Support\Services\SupportDashboardService::class] = new \SkyFi\Support\Services\SupportDashboardService($this->instances[\SkyFi\Support\Repositories\PdoTicketRepository::class]);
        $this->instances[\SkyFi\Support\Contracts\SupportDashboardServiceContract::class] = $this->instances[\SkyFi\Support\Services\SupportDashboardService::class];
        $this->instances[\SkyFi\Support\Controllers\TicketController::class] = new \SkyFi\Support\Controllers\TicketController($this->instances[\SkyFi\Support\Services\TicketService::class],$this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Support\Controllers\TicketActionController::class] = new \SkyFi\Support\Controllers\TicketActionController($this->instances[\SkyFi\Support\Services\TicketService::class],$this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Support\Controllers\TicketCommentController::class] = new \SkyFi\Support\Controllers\TicketCommentController($this->instances[\SkyFi\Support\Services\TicketService::class],$this->instances[\SkyFi\Support\Repositories\PdoTicketCommentRepository::class],$this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Support\Controllers\TicketTimelineController::class] = new \SkyFi\Support\Controllers\TicketTimelineController($this->instances[\SkyFi\Support\Repositories\PdoTicketRepository::class],$this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Support\Controllers\SupportDashboardController::class] = new \SkyFi\Support\Controllers\SupportDashboardController($this->instances[\SkyFi\Support\Services\SupportDashboardService::class],$this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Support\Controllers\SupportLookupController::class] = new \SkyFi\Support\Controllers\SupportLookupController($this->instances[\SkyFi\Support\Repositories\PdoTicketRepository::class],$this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Support\Controllers\SupportConfigurationController::class] = new \SkyFi\Support\Controllers\SupportConfigurationController($this->instances[\SkyFi\Support\Repositories\PdoTicketRepository::class],$this->instances[RequirePermissionMiddleware::class]);
        // ─── End Support Ticket & Helpdesk Module ────────────────────────

        // ─── Inventory & Asset Management Module ─────────────────────────
        $this->instances[\SkyFi\Inventory\Repositories\PdoCatalogRepository::class] = new \SkyFi\Inventory\Repositories\PdoCatalogRepository($pdo);
        $this->instances[\SkyFi\Inventory\Contracts\CatalogRepositoryContract::class] = $this->instances[\SkyFi\Inventory\Repositories\PdoCatalogRepository::class];
        $this->instances[\SkyFi\Inventory\Repositories\PdoProductRepository::class] = new \SkyFi\Inventory\Repositories\PdoProductRepository($pdo);
        $this->instances[\SkyFi\Inventory\Contracts\ProductRepositoryContract::class] = $this->instances[\SkyFi\Inventory\Repositories\PdoProductRepository::class];
        $this->instances[\SkyFi\Inventory\Repositories\PdoWarehouseRepository::class] = new \SkyFi\Inventory\Repositories\PdoWarehouseRepository($pdo);
        $this->instances[\SkyFi\Inventory\Contracts\WarehouseRepositoryContract::class] = $this->instances[\SkyFi\Inventory\Repositories\PdoWarehouseRepository::class];
        $this->instances[\SkyFi\Inventory\Repositories\PdoAssetRepository::class] = new \SkyFi\Inventory\Repositories\PdoAssetRepository($pdo);
        $this->instances[\SkyFi\Inventory\Contracts\AssetRepositoryContract::class] = $this->instances[\SkyFi\Inventory\Repositories\PdoAssetRepository::class];
        $this->instances[\SkyFi\Inventory\Repositories\PdoStockRepository::class] = new \SkyFi\Inventory\Repositories\PdoStockRepository($pdo);
        $this->instances[\SkyFi\Inventory\Contracts\StockRepositoryContract::class] = $this->instances[\SkyFi\Inventory\Repositories\PdoStockRepository::class];
        $this->instances[\SkyFi\Inventory\Repositories\PdoTransferRepository::class] = new \SkyFi\Inventory\Repositories\PdoTransferRepository($pdo);
        $this->instances[\SkyFi\Inventory\Contracts\TransferRepositoryContract::class] = $this->instances[\SkyFi\Inventory\Repositories\PdoTransferRepository::class];

        $this->instances[\SkyFi\Inventory\Validators\ProductValidator::class] = new \SkyFi\Inventory\Validators\ProductValidator();
        $this->instances[\SkyFi\Inventory\Validators\WarehouseValidator::class] = new \SkyFi\Inventory\Validators\WarehouseValidator();
        $this->instances[\SkyFi\Inventory\Validators\AssetValidator::class] = new \SkyFi\Inventory\Validators\AssetValidator();
        $this->instances[\SkyFi\Inventory\Validators\StockValidator::class] = new \SkyFi\Inventory\Validators\StockValidator();
        $this->instances[\SkyFi\Inventory\Validators\TransferValidator::class] = new \SkyFi\Inventory\Validators\TransferValidator();

        $this->instances[\SkyFi\Inventory\Services\CatalogService::class] = new \SkyFi\Inventory\Services\CatalogService(
            $this->instances[\SkyFi\Inventory\Repositories\PdoCatalogRepository::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Inventory\Services\ProductService::class] = new \SkyFi\Inventory\Services\ProductService(
            $this->instances[\SkyFi\Inventory\Repositories\PdoProductRepository::class],
            $this->instances[\SkyFi\Inventory\Validators\ProductValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Inventory\Services\WarehouseService::class] = new \SkyFi\Inventory\Services\WarehouseService(
            $this->instances[\SkyFi\Inventory\Repositories\PdoWarehouseRepository::class],
            $this->instances[\SkyFi\Inventory\Validators\WarehouseValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Inventory\Services\AssetService::class] = new \SkyFi\Inventory\Services\AssetService(
            $this->instances[\SkyFi\Inventory\Repositories\PdoAssetRepository::class],
            $this->instances[\SkyFi\Inventory\Repositories\PdoProductRepository::class],
            $this->instances[\SkyFi\Inventory\Validators\AssetValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Inventory\Services\InventoryFinanceIntegrationService::class] = new \SkyFi\Inventory\Services\InventoryFinanceIntegrationService(
            $pdo,
            $this->instances[\SkyFi\Finance\Services\FinanceService::class],
        );
        $this->instances[\SkyFi\Inventory\Services\StockService::class] = new \SkyFi\Inventory\Services\StockService(
            $this->instances[\SkyFi\Inventory\Repositories\PdoStockRepository::class],
            $this->instances[\SkyFi\Inventory\Validators\StockValidator::class],
            $this->instances[PdoAuditLogger::class],
            $this->instances[\SkyFi\Inventory\Services\InventoryFinanceIntegrationService::class],
        );
        $this->instances[\SkyFi\Inventory\Services\TransferService::class] = new \SkyFi\Inventory\Services\TransferService(
            $this->instances[\SkyFi\Inventory\Repositories\PdoTransferRepository::class],
            $this->instances[\SkyFi\Inventory\Repositories\PdoStockRepository::class],
            $this->instances[\SkyFi\Inventory\Validators\TransferValidator::class],
            $this->instances[PdoAuditLogger::class],
        );

        $this->instances[\SkyFi\Inventory\Controllers\ProductController::class] = new \SkyFi\Inventory\Controllers\ProductController($this->instances[\SkyFi\Inventory\Services\ProductService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Inventory\Controllers\CatalogController::class] = new \SkyFi\Inventory\Controllers\CatalogController($this->instances[\SkyFi\Inventory\Services\CatalogService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Inventory\Controllers\WarehouseController::class] = new \SkyFi\Inventory\Controllers\WarehouseController($this->instances[\SkyFi\Inventory\Services\WarehouseService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Inventory\Controllers\AssetController::class] = new \SkyFi\Inventory\Controllers\AssetController($this->instances[\SkyFi\Inventory\Services\AssetService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Inventory\Controllers\StockController::class] = new \SkyFi\Inventory\Controllers\StockController($this->instances[\SkyFi\Inventory\Services\StockService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Inventory\Controllers\TransferController::class] = new \SkyFi\Inventory\Controllers\TransferController($this->instances[\SkyFi\Inventory\Services\TransferService::class], $this->instances[RequirePermissionMiddleware::class]);
        $this->instances[\SkyFi\Inventory\Controllers\InventoryLookupController::class] = new \SkyFi\Inventory\Controllers\InventoryLookupController($this->instances[\SkyFi\Inventory\Services\CatalogService::class], $this->instances[RequirePermissionMiddleware::class]);
        // ─── End Inventory & Asset Management Module ─────────────────────

        // ─── Purchasing & Procurement Module ─────────────────────────────
        $this->instances[\SkyFi\Purchasing\Repositories\PdoPurchaseRequestRepository::class] = new \SkyFi\Purchasing\Repositories\PdoPurchaseRequestRepository($pdo);
        $this->instances[\SkyFi\Purchasing\Contracts\PurchaseRequestRepositoryContract::class] = $this->instances[\SkyFi\Purchasing\Repositories\PdoPurchaseRequestRepository::class];
        $this->instances[\SkyFi\Purchasing\Repositories\PdoPurchaseOrderRepository::class] = new \SkyFi\Purchasing\Repositories\PdoPurchaseOrderRepository($pdo);
        $this->instances[\SkyFi\Purchasing\Contracts\PurchaseOrderRepositoryContract::class] = $this->instances[\SkyFi\Purchasing\Repositories\PdoPurchaseOrderRepository::class];
        $this->instances[\SkyFi\Purchasing\Repositories\PdoGoodsReceiptRepository::class] = new \SkyFi\Purchasing\Repositories\PdoGoodsReceiptRepository($pdo);
        $this->instances[\SkyFi\Purchasing\Contracts\GoodsReceiptRepositoryContract::class] = $this->instances[\SkyFi\Purchasing\Repositories\PdoGoodsReceiptRepository::class];
        $this->instances[\SkyFi\Purchasing\Repositories\PdoSupplierInvoiceRepository::class] = new \SkyFi\Purchasing\Repositories\PdoSupplierInvoiceRepository($pdo);
        $this->instances[\SkyFi\Purchasing\Contracts\SupplierInvoiceRepositoryContract::class] = $this->instances[\SkyFi\Purchasing\Repositories\PdoSupplierInvoiceRepository::class];

        $this->instances[\SkyFi\Purchasing\Validators\PurchaseRequestValidator::class] = new \SkyFi\Purchasing\Validators\PurchaseRequestValidator();
        $this->instances[\SkyFi\Purchasing\Validators\PurchaseOrderValidator::class] = new \SkyFi\Purchasing\Validators\PurchaseOrderValidator();
        $this->instances[\SkyFi\Purchasing\Validators\GoodsReceiptValidator::class] = new \SkyFi\Purchasing\Validators\GoodsReceiptValidator();
        $this->instances[\SkyFi\Purchasing\Validators\SupplierInvoiceValidator::class] = new \SkyFi\Purchasing\Validators\SupplierInvoiceValidator();

        $this->instances[\SkyFi\Purchasing\Services\PurchasingFinanceIntegrationService::class] = new \SkyFi\Purchasing\Services\PurchasingFinanceIntegrationService(
            $pdo,
            $this->instances[\SkyFi\Finance\Services\FinanceService::class],
        );

        $this->instances[\SkyFi\Purchasing\Services\PurchaseRequestService::class] = new \SkyFi\Purchasing\Services\PurchaseRequestService(
            $this->instances[\SkyFi\Purchasing\Repositories\PdoPurchaseRequestRepository::class],
            $this->instances[\SkyFi\Purchasing\Validators\PurchaseRequestValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Purchasing\Services\PurchaseOrderService::class] = new \SkyFi\Purchasing\Services\PurchaseOrderService(
            $this->instances[\SkyFi\Purchasing\Repositories\PdoPurchaseOrderRepository::class],
            $this->instances[\SkyFi\Purchasing\Validators\PurchaseOrderValidator::class],
            $this->instances[PdoAuditLogger::class],
            $this->instances[\SkyFi\Purchasing\Services\PurchasingFinanceIntegrationService::class],
        );
        $this->instances[\SkyFi\Purchasing\Services\GoodsReceiptService::class] = new \SkyFi\Purchasing\Services\GoodsReceiptService(
            $this->instances[\SkyFi\Purchasing\Repositories\PdoGoodsReceiptRepository::class],
            $this->instances[\SkyFi\Purchasing\Repositories\PdoPurchaseOrderRepository::class],
            $this->instances[\SkyFi\Purchasing\Validators\GoodsReceiptValidator::class],
            $this->instances[PdoAuditLogger::class],
            $pdo,
            $this->instances[\SkyFi\Purchasing\Services\PurchasingFinanceIntegrationService::class],
        );
        $this->instances[\SkyFi\Purchasing\Services\SupplierInvoiceService::class] = new \SkyFi\Purchasing\Services\SupplierInvoiceService(
            $this->instances[\SkyFi\Purchasing\Repositories\PdoSupplierInvoiceRepository::class],
            $this->instances[\SkyFi\Purchasing\Validators\SupplierInvoiceValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Purchasing\Services\PurchasingDashboardService::class] = new \SkyFi\Purchasing\Services\PurchasingDashboardService($pdo);

        $this->instances[\SkyFi\Purchasing\Controllers\PurchaseRequestController::class] = new \SkyFi\Purchasing\Controllers\PurchaseRequestController(
            $this->instances[\SkyFi\Purchasing\Services\PurchaseRequestService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Purchasing\Controllers\PurchaseOrderController::class] = new \SkyFi\Purchasing\Controllers\PurchaseOrderController(
            $this->instances[\SkyFi\Purchasing\Services\PurchaseOrderService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Purchasing\Controllers\GoodsReceiptController::class] = new \SkyFi\Purchasing\Controllers\GoodsReceiptController(
            $this->instances[\SkyFi\Purchasing\Services\GoodsReceiptService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Purchasing\Controllers\SupplierInvoiceController::class] = new \SkyFi\Purchasing\Controllers\SupplierInvoiceController(
            $this->instances[\SkyFi\Purchasing\Services\SupplierInvoiceService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Purchasing\Controllers\PurchasingDashboardController::class] = new \SkyFi\Purchasing\Controllers\PurchasingDashboardController(
            $this->instances[\SkyFi\Purchasing\Services\PurchasingDashboardService::class],
            $this->instances[\SkyFi\Purchasing\Services\PurchasingFinanceIntegrationService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        // ─── End Purchasing & Procurement Module ─────────────────────────

        // ─── Vendor & Supplier Management Module ─────────────────────────
        $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierRepository::class] = new \SkyFi\Vendors\Repositories\PdoSupplierRepository($pdo);
        $this->instances[\SkyFi\Vendors\Contracts\SupplierRepositoryContract::class] = $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierRepository::class];
        $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierContactRepository::class] = new \SkyFi\Vendors\Repositories\PdoSupplierContactRepository($pdo);
        $this->instances[\SkyFi\Vendors\Contracts\SupplierContactRepositoryContract::class] = $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierContactRepository::class];
        $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierContractRepository::class] = new \SkyFi\Vendors\Repositories\PdoSupplierContractRepository($pdo);
        $this->instances[\SkyFi\Vendors\Contracts\SupplierContractRepositoryContract::class] = $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierContractRepository::class];
        $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierQuotationRepository::class] = new \SkyFi\Vendors\Repositories\PdoSupplierQuotationRepository($pdo);
        $this->instances[\SkyFi\Vendors\Contracts\SupplierQuotationRepositoryContract::class] = $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierQuotationRepository::class];
        $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierRatingRepository::class] = new \SkyFi\Vendors\Repositories\PdoSupplierRatingRepository($pdo);
        $this->instances[\SkyFi\Vendors\Contracts\SupplierRatingRepositoryContract::class] = $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierRatingRepository::class];

        $this->instances[\SkyFi\Vendors\Validators\SupplierValidator::class] = new \SkyFi\Vendors\Validators\SupplierValidator();
        $this->instances[\SkyFi\Vendors\Validators\ContactValidator::class] = new \SkyFi\Vendors\Validators\ContactValidator();
        $this->instances[\SkyFi\Vendors\Validators\ContractValidator::class] = new \SkyFi\Vendors\Validators\ContractValidator();
        $this->instances[\SkyFi\Vendors\Validators\QuotationValidator::class] = new \SkyFi\Vendors\Validators\QuotationValidator();
        $this->instances[\SkyFi\Vendors\Validators\RatingValidator::class] = new \SkyFi\Vendors\Validators\RatingValidator();

        $this->instances[\SkyFi\Vendors\Services\SupplierService::class] = new \SkyFi\Vendors\Services\SupplierService(
            $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierRepository::class],
            $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierContactRepository::class],
            $this->instances[\SkyFi\Vendors\Validators\SupplierValidator::class],
            $this->instances[PdoAuditLogger::class],
            $pdo,
        );
        $this->instances[\SkyFi\Vendors\Services\SupplierContactService::class] = new \SkyFi\Vendors\Services\SupplierContactService(
            $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierContactRepository::class],
            $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierRepository::class],
            $this->instances[\SkyFi\Vendors\Validators\ContactValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Vendors\Services\SupplierContractService::class] = new \SkyFi\Vendors\Services\SupplierContractService(
            $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierContractRepository::class],
            $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierRepository::class],
            $this->instances[\SkyFi\Vendors\Validators\ContractValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Vendors\Services\SupplierQuotationService::class] = new \SkyFi\Vendors\Services\SupplierQuotationService(
            $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierQuotationRepository::class],
            $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierRepository::class],
            $this->instances[\SkyFi\Vendors\Validators\QuotationValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Vendors\Services\SupplierPerformanceService::class] = new \SkyFi\Vendors\Services\SupplierPerformanceService(
            $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierRatingRepository::class],
            $this->instances[\SkyFi\Vendors\Repositories\PdoSupplierRepository::class],
            $this->instances[\SkyFi\Vendors\Validators\RatingValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\Vendors\Services\VendorDashboardService::class] = new \SkyFi\Vendors\Services\VendorDashboardService($pdo);

        $permission = $this->instances[RequirePermissionMiddleware::class];
        $this->instances[\SkyFi\Vendors\Controllers\SupplierController::class] = new \SkyFi\Vendors\Controllers\SupplierController($this->instances[\SkyFi\Vendors\Services\SupplierService::class], $permission);
        $this->instances[\SkyFi\Vendors\Controllers\SupplierCategoryController::class] = new \SkyFi\Vendors\Controllers\SupplierCategoryController($this->instances[\SkyFi\Vendors\Services\SupplierService::class], $permission);
        $this->instances[\SkyFi\Vendors\Controllers\SupplierContactController::class] = new \SkyFi\Vendors\Controllers\SupplierContactController($this->instances[\SkyFi\Vendors\Services\SupplierContactService::class], $permission);
        $this->instances[\SkyFi\Vendors\Controllers\SupplierContractController::class] = new \SkyFi\Vendors\Controllers\SupplierContractController($this->instances[\SkyFi\Vendors\Services\SupplierContractService::class], $permission);
        $this->instances[\SkyFi\Vendors\Controllers\SupplierQuotationController::class] = new \SkyFi\Vendors\Controllers\SupplierQuotationController($this->instances[\SkyFi\Vendors\Services\SupplierQuotationService::class], $permission);
        $this->instances[\SkyFi\Vendors\Controllers\SupplierPerformanceController::class] = new \SkyFi\Vendors\Controllers\SupplierPerformanceController($this->instances[\SkyFi\Vendors\Services\SupplierPerformanceService::class], $permission);
        $this->instances[\SkyFi\Vendors\Controllers\VendorDashboardController::class] = new \SkyFi\Vendors\Controllers\VendorDashboardController($this->instances[\SkyFi\Vendors\Services\VendorDashboardService::class], $permission);
        // ─── End Vendor & Supplier Management Module ─────────────────────

        // ─── Customer Installation & Field Service Module ────────────────
        $this->instances[\SkyFi\FieldService\Repositories\PdoFieldServiceRepository::class] = new \SkyFi\FieldService\Repositories\PdoFieldServiceRepository($pdo);
        $this->instances[\SkyFi\FieldService\Contracts\FieldServiceRepositoryContract::class] = $this->instances[\SkyFi\FieldService\Repositories\PdoFieldServiceRepository::class];
        $this->instances[\SkyFi\FieldService\Validators\FieldServiceValidator::class] = new \SkyFi\FieldService\Validators\FieldServiceValidator();
        $this->instances[\SkyFi\FieldService\Validators\FieldOperationValidator::class] = new \SkyFi\FieldService\Validators\FieldOperationValidator();
        $this->instances[\SkyFi\FieldService\Services\InstallationRequestService::class] = new \SkyFi\FieldService\Services\InstallationRequestService(
            $this->instances[\SkyFi\FieldService\Repositories\PdoFieldServiceRepository::class],
            $this->instances[\SkyFi\FieldService\Validators\FieldServiceValidator::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\FieldService\Services\WorkOrderService::class] = new \SkyFi\FieldService\Services\WorkOrderService(
            $this->instances[\SkyFi\FieldService\Repositories\PdoFieldServiceRepository::class],
            $this->instances[\SkyFi\FieldService\Validators\FieldServiceValidator::class],
            $this->instances[\SkyFi\FieldService\Validators\FieldOperationValidator::class],
            $this->instances[\SkyFi\Inventory\Services\StockService::class],
            $this->instances[ConnectionService::class],
            $this->instances[CustomerService::class],
            $this->instances[BillingScheduleRepositoryContract::class],
            $this->instances[PdoAuditLogger::class],
        );
        $this->instances[\SkyFi\FieldService\Contracts\FieldServiceServiceContract::class] = $this->instances[\SkyFi\FieldService\Services\WorkOrderService::class];
        $this->instances[\SkyFi\FieldService\Services\TechnicianService::class] = new \SkyFi\FieldService\Services\TechnicianService($this->instances[\SkyFi\FieldService\Repositories\PdoFieldServiceRepository::class], $this->instances[\SkyFi\FieldService\Validators\FieldServiceValidator::class], $this->instances[PdoAuditLogger::class]);
        $this->instances[\SkyFi\FieldService\Services\SchedulerService::class] = new \SkyFi\FieldService\Services\SchedulerService($this->instances[\SkyFi\FieldService\Repositories\PdoFieldServiceRepository::class]);
        $this->instances[\SkyFi\FieldService\Services\FieldServiceDashboardService::class] = new \SkyFi\FieldService\Services\FieldServiceDashboardService($this->instances[\SkyFi\FieldService\Repositories\PdoFieldServiceRepository::class]);
        $this->instances[\SkyFi\FieldService\Services\FieldOperationService::class] = new \SkyFi\FieldService\Services\FieldOperationService($this->instances[\SkyFi\FieldService\Repositories\PdoFieldServiceRepository::class], $this->instances[\SkyFi\FieldService\Validators\FieldOperationValidator::class], $this->instances[\SkyFi\FieldService\Validators\FieldServiceValidator::class]);
        $this->instances[\SkyFi\FieldService\Controllers\FieldServiceController::class] = new \SkyFi\FieldService\Controllers\FieldServiceController($this->instances[\SkyFi\FieldService\Services\InstallationRequestService::class], $this->instances[\SkyFi\FieldService\Services\WorkOrderService::class], $this->instances[\SkyFi\FieldService\Services\SchedulerService::class], $this->instances[\SkyFi\FieldService\Services\FieldServiceDashboardService::class], $permission);
        $this->instances[\SkyFi\FieldService\Controllers\TechnicianController::class] = new \SkyFi\FieldService\Controllers\TechnicianController($this->instances[\SkyFi\FieldService\Services\TechnicianService::class], $this->instances[\SkyFi\FieldService\Repositories\PdoFieldServiceRepository::class], $permission);
        $this->instances[\SkyFi\FieldService\Controllers\FieldOperationController::class] = new \SkyFi\FieldService\Controllers\FieldOperationController($this->instances[\SkyFi\FieldService\Services\FieldOperationService::class], $permission);
        \SkyFi\Shared\Events\EventDispatcher::listen('connection.approved', function(array $payload): void {
            $this->instances[\SkyFi\FieldService\Services\InstallationRequestService::class]->createFromApprovedConnection($payload['connection'], (int) $payload['actor_id']);
        });
        // ─── End Customer Installation & Field Service Module ────────────

        // ─── Reports & Business Intelligence Module ─────────────────────
        $this->instances[\SkyFi\Reports\Services\ReportCatalog::class] = new \SkyFi\Reports\Services\ReportCatalog();
        $this->instances[\SkyFi\Reports\QueryBuilders\ReportQueryBuilder::class] = new \SkyFi\Reports\QueryBuilders\ReportQueryBuilder();
        $this->instances[\SkyFi\Reports\Contracts\ReportQueryBuilderContract::class] = $this->instances[\SkyFi\Reports\QueryBuilders\ReportQueryBuilder::class];
        $this->instances[\SkyFi\Reports\Repositories\PdoReportRepository::class] = new \SkyFi\Reports\Repositories\PdoReportRepository($pdo, $this->instances[\SkyFi\Reports\QueryBuilders\ReportQueryBuilder::class]);
        $this->instances[\SkyFi\Reports\Contracts\ReportRepositoryContract::class] = $this->instances[\SkyFi\Reports\Repositories\PdoReportRepository::class];
        $this->instances[\SkyFi\Reports\Validators\ReportRequestValidator::class] = new \SkyFi\Reports\Validators\ReportRequestValidator($this->instances[\SkyFi\Reports\Services\ReportCatalog::class]);
        $this->instances[\SkyFi\Reports\Services\ReportService::class] = new \SkyFi\Reports\Services\ReportService($this->instances[\SkyFi\Reports\Repositories\PdoReportRepository::class], $this->instances[\SkyFi\Reports\Services\ReportCatalog::class], $this->instances[\SkyFi\Reports\Validators\ReportRequestValidator::class]);
        $this->instances[\SkyFi\Reports\Contracts\ReportServiceContract::class] = $this->instances[\SkyFi\Reports\Services\ReportService::class];
        $this->instances[\SkyFi\Reports\Services\ReportDashboardService::class] = new \SkyFi\Reports\Services\ReportDashboardService($this->instances[\SkyFi\Reports\Services\ReportService::class]);
        $this->instances[\SkyFi\Reports\Repositories\PdoReportConfigurationRepository::class] = new \SkyFi\Reports\Repositories\PdoReportConfigurationRepository($pdo);
        $this->instances[\SkyFi\Reports\Services\ReportConfigurationService::class] = new \SkyFi\Reports\Services\ReportConfigurationService($this->instances[\SkyFi\Reports\Repositories\PdoReportConfigurationRepository::class], $this->instances[\SkyFi\Reports\Services\ReportCatalog::class]);
        $this->instances[\SkyFi\Reports\ExportServices\ReportExportService::class] = new \SkyFi\Reports\ExportServices\ReportExportService($this->instances[\SkyFi\Reports\Services\ReportService::class], $this->instances[\SkyFi\Reports\Repositories\PdoReportConfigurationRepository::class], dirname(__DIR__, 3) . '/storage/exports');
        $this->instances[\SkyFi\Reports\Controllers\ReportController::class] = new \SkyFi\Reports\Controllers\ReportController($this->instances[\SkyFi\Reports\Services\ReportService::class], $this->instances[\SkyFi\Reports\Services\ReportDashboardService::class], $this->instances[\SkyFi\Reports\Services\ReportCatalog::class], $this->instances[\SkyFi\Reports\Repositories\PdoReportRepository::class], $permission);
        $this->instances[\SkyFi\Reports\Controllers\ReportConfigurationController::class] = new \SkyFi\Reports\Controllers\ReportConfigurationController($this->instances[\SkyFi\Reports\Services\ReportConfigurationService::class], $this->instances[\SkyFi\Reports\Services\ReportService::class], $permission);
        $this->instances[\SkyFi\Reports\Controllers\ReportExportController::class] = new \SkyFi\Reports\Controllers\ReportExportController($this->instances[\SkyFi\Reports\ExportServices\ReportExportService::class], $permission);
        // ─── End Reports & Business Intelligence Module ──────────────────

        // ─── Notification Center Module ─────────────────────────────────
        $this->instances[\SkyFi\Notifications\Services\NotificationCatalog::class] = new \SkyFi\Notifications\Services\NotificationCatalog();
        $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationRepository::class] = new \SkyFi\Notifications\Repositories\PdoNotificationRepository($pdo);
        $this->instances[\SkyFi\Notifications\Contracts\NotificationRepositoryContract::class] = $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationRepository::class];
        $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationTemplateRepository::class] = new \SkyFi\Notifications\Repositories\PdoNotificationTemplateRepository($pdo);
        $this->instances[\SkyFi\Notifications\Contracts\NotificationTemplateRepositoryContract::class] = $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationTemplateRepository::class];
        $this->instances[\SkyFi\Notifications\Repositories\PdoUserPreferenceRepository::class] = new \SkyFi\Notifications\Repositories\PdoUserPreferenceRepository($pdo);
        $this->instances[\SkyFi\Notifications\Contracts\UserPreferenceRepositoryContract::class] = $this->instances[\SkyFi\Notifications\Repositories\PdoUserPreferenceRepository::class];
        $this->instances[\SkyFi\Notifications\Repositories\PdoDeliveryHistoryRepository::class] = new \SkyFi\Notifications\Repositories\PdoDeliveryHistoryRepository($pdo);
        $this->instances[\SkyFi\Notifications\Contracts\DeliveryHistoryRepositoryContract::class] = $this->instances[\SkyFi\Notifications\Repositories\PdoDeliveryHistoryRepository::class];
        $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationEventRepository::class] = new \SkyFi\Notifications\Repositories\PdoNotificationEventRepository($pdo);
        $this->instances[\SkyFi\Notifications\Contracts\NotificationEventRepositoryContract::class] = $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationEventRepository::class];
        $this->instances[\SkyFi\Notifications\Services\DeliveryService::class] = new \SkyFi\Notifications\Services\DeliveryService(
            $this->instances[\SkyFi\Notifications\Repositories\PdoDeliveryHistoryRepository::class],
            $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationTemplateRepository::class],
            $this->instances[\SkyFi\Notifications\Repositories\PdoUserPreferenceRepository::class],
            [
                new \SkyFi\Notifications\Channels\InAppChannel(),
                new \SkyFi\Notifications\Channels\EmailChannel(),
                new \SkyFi\Notifications\Channels\SmsChannel(),
                new \SkyFi\Notifications\Channels\PushChannel(),
                new \SkyFi\Notifications\Channels\WebhookChannel(),
            ],
        );
        $this->instances[\SkyFi\Notifications\EventPublishers\NotificationEventPublisher::class] = new \SkyFi\Notifications\EventPublishers\NotificationEventPublisher(
            $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationEventRepository::class],
        );
        $this->instances[\SkyFi\Notifications\Validators\NotificationValidator::class] = new \SkyFi\Notifications\Validators\NotificationValidator(
            $this->instances[\SkyFi\Notifications\Services\NotificationCatalog::class],
        );
        $this->instances[\SkyFi\Notifications\Validators\TemplateValidator::class] = new \SkyFi\Notifications\Validators\TemplateValidator(
            $this->instances[\SkyFi\Notifications\Services\NotificationCatalog::class],
        );
        $this->instances[\SkyFi\Notifications\Validators\PreferenceValidator::class] = new \SkyFi\Notifications\Validators\PreferenceValidator(
            $this->instances[\SkyFi\Notifications\Services\NotificationCatalog::class],
        );
        $this->instances[\SkyFi\Notifications\Services\NotificationService::class] = new \SkyFi\Notifications\Services\NotificationService(
            $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationRepository::class],
            $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationTemplateRepository::class],
            $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationEventRepository::class],
            $this->instances[\SkyFi\Notifications\Services\DeliveryService::class],
            $this->instances[\SkyFi\Notifications\EventPublishers\NotificationEventPublisher::class],
            $this->instances[\SkyFi\Notifications\Services\NotificationCatalog::class],
            $this->instances[\SkyFi\Notifications\Validators\NotificationValidator::class],
            $pdo,
        );
        $this->instances[\SkyFi\Notifications\Contracts\NotificationServiceContract::class] = $this->instances[\SkyFi\Notifications\Services\NotificationService::class];
        $this->instances[\SkyFi\Notifications\Services\TemplateService::class] = new \SkyFi\Notifications\Services\TemplateService(
            $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationTemplateRepository::class],
            $this->instances[\SkyFi\Notifications\Validators\TemplateValidator::class],
            $this->instances[\SkyFi\Notifications\Services\DeliveryService::class],
        );
        $this->instances[\SkyFi\Notifications\Services\PreferenceService::class] = new \SkyFi\Notifications\Services\PreferenceService(
            $this->instances[\SkyFi\Notifications\Repositories\PdoUserPreferenceRepository::class],
            $this->instances[\SkyFi\Notifications\Validators\PreferenceValidator::class],
            $this->instances[\SkyFi\Notifications\Services\NotificationCatalog::class],
        );
        $this->instances[\SkyFi\Notifications\Controllers\NotificationController::class] = new \SkyFi\Notifications\Controllers\NotificationController(
            $this->instances[\SkyFi\Notifications\Services\NotificationService::class],
            $permission,
        );
        $this->instances[\SkyFi\Notifications\Controllers\NotificationTemplateController::class] = new \SkyFi\Notifications\Controllers\NotificationTemplateController(
            $this->instances[\SkyFi\Notifications\Services\TemplateService::class],
            $permission,
        );
        $this->instances[\SkyFi\Notifications\Controllers\UserPreferenceController::class] = new \SkyFi\Notifications\Controllers\UserPreferenceController(
            $this->instances[\SkyFi\Notifications\Services\PreferenceService::class],
            $permission,
        );
        $this->instances[\SkyFi\Notifications\Controllers\DeliveryHistoryController::class] = new \SkyFi\Notifications\Controllers\DeliveryHistoryController(
            $this->instances[\SkyFi\Notifications\Repositories\PdoDeliveryHistoryRepository::class],
            $permission,
        );
        $this->instances[\SkyFi\Notifications\Controllers\NotificationEventController::class] = new \SkyFi\Notifications\Controllers\NotificationEventController(
            $this->instances[\SkyFi\Notifications\Repositories\PdoNotificationEventRepository::class],
            $permission,
        );
        $this->instances[\SkyFi\Notifications\EventSubscribers\DomainEventSubscriber::class] = new \SkyFi\Notifications\EventSubscribers\DomainEventSubscriber(
            $this->instances[\SkyFi\Notifications\Services\NotificationService::class],
        );
        $this->instances[\SkyFi\Notifications\EventSubscribers\DomainEventSubscriber::class]->register();
        // ─── End Notification Center Module ─────────────────────────────

        // ─── Audit, Compliance & Activity Center Module ──────────────────
        $this->instances[PdoAuditLogRepository::class] = new PdoAuditLogRepository($pdo);
        $this->instances[AuditLogRepositoryContract::class] = $this->instances[PdoAuditLogRepository::class];
        $this->instances[PdoActivityRepository::class] = new PdoActivityRepository($pdo);
        $this->instances[ActivityRepositoryContract::class] = $this->instances[PdoActivityRepository::class];
        $this->instances[PdoComplianceRepository::class] = new PdoComplianceRepository($pdo);
        $this->instances[ComplianceRepositoryContract::class] = $this->instances[PdoComplianceRepository::class];
        $this->instances[PdoRetentionRepository::class] = new PdoRetentionRepository($pdo);
        $this->instances[RetentionRepositoryContract::class] = $this->instances[PdoRetentionRepository::class];
        $this->instances[PdoAuditExportRepository::class] = new PdoAuditExportRepository($pdo);
        $this->instances[AuditExportRepositoryContract::class] = $this->instances[PdoAuditExportRepository::class];
        $this->instances[AuditValidator::class] = new AuditValidator();
        $this->instances[AuditExportService::class] = new AuditExportService(
            $this->instances[PdoAuditLogRepository::class],
            $this->instances[PdoAuditExportRepository::class],
            dirname(__DIR__, 3) . '/storage/exports',
        );
        $this->instances[AuditService::class] = new AuditService(
            $this->instances[PdoAuditLogRepository::class],
            $this->instances[PdoActivityRepository::class],
            $this->instances[PdoAuditExportRepository::class],
            $this->instances[AuditExportService::class],
        );
        $this->instances[AuditServiceContract::class] = $this->instances[AuditService::class];
        $this->instances[ComplianceService::class] = new ComplianceService(
            $this->instances[PdoComplianceRepository::class],
            $this->instances[PdoRetentionRepository::class],
        );
        $this->instances[ComplianceServiceContract::class] = $this->instances[ComplianceService::class];
        $this->instances[AuditLogController::class] = new AuditLogController(
            $this->instances[AuditService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[ActivityController::class] = new ActivityController(
            $this->instances[AuditService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[AuditExportController::class] = new AuditExportController(
            $this->instances[AuditService::class],
            $this->instances[AuditValidator::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[ComplianceController::class] = new ComplianceController(
            $this->instances[ComplianceService::class],
            $this->instances[AuditValidator::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        // Register audit event subscriber
        $this->instances[AuditEventSubscriber::class] = new AuditEventSubscriber($this->instances[AuditService::class]);
        $this->instances[AuditEventSubscriber::class]->register();
        // ─── End Audit, Compliance & Activity Center Module ─────────────

        // ─── Backup, Restore & Disaster Recovery Module ──────────────────
        $this->instances[\SkyFi\Backup\Contracts\BackupJobRepositoryContract::class] = new \SkyFi\Backup\Repositories\PdoBackupJobRepository($pdo);
        $this->instances[\SkyFi\Backup\Contracts\BackupScheduleRepositoryContract::class] = new \SkyFi\Backup\Repositories\PdoBackupScheduleRepository($pdo);
        $this->instances[\SkyFi\Backup\Contracts\BackupFileRepositoryContract::class] = new \SkyFi\Backup\Repositories\PdoBackupFileRepository($pdo);
        $this->instances[\SkyFi\Backup\Repositories\PdoStorageProviderRepository::class] = new \SkyFi\Backup\Repositories\PdoStorageProviderRepository($pdo);
        $this->instances[\SkyFi\Backup\Repositories\PdoDrPlanRepository::class] = new \SkyFi\Backup\Repositories\PdoDrPlanRepository($pdo);

        $this->instances[\SkyFi\Backup\Services\BackupService::class] = new \SkyFi\Backup\Services\BackupService(
            $this->instances[\SkyFi\Backup\Contracts\BackupJobRepositoryContract::class],
            $this->instances[\SkyFi\Backup\Contracts\BackupFileRepositoryContract::class],
            $this->instances[\SkyFi\Backup\Repositories\PdoStorageProviderRepository::class]
        );
        $this->instances[\SkyFi\Backup\Services\RestoreService::class] = new \SkyFi\Backup\Services\RestoreService(
            $this->instances[\SkyFi\Backup\Contracts\BackupFileRepositoryContract::class],
            $pdo
        );
        $this->instances[\SkyFi\Backup\Services\BackupScheduler::class] = new \SkyFi\Backup\Services\BackupScheduler(
            $this->instances[\SkyFi\Backup\Contracts\BackupScheduleRepositoryContract::class],
            $this->instances[\SkyFi\Backup\Services\BackupService::class],
            $this->instances[\SkyFi\Backup\Contracts\BackupFileRepositoryContract::class]
        );

        $this->instances[\SkyFi\Backup\Controllers\BackupController::class] = new \SkyFi\Backup\Controllers\BackupController(
            $this->instances[\SkyFi\Backup\Contracts\BackupJobRepositoryContract::class],
            $this->instances[\SkyFi\Backup\Contracts\BackupFileRepositoryContract::class],
            $this->instances[\SkyFi\Backup\Services\BackupService::class]
        );
        $this->instances[\SkyFi\Backup\Controllers\ScheduleController::class] = new \SkyFi\Backup\Controllers\ScheduleController(
            $this->instances[\SkyFi\Backup\Contracts\BackupScheduleRepositoryContract::class]
        );
        $this->instances[\SkyFi\Backup\Controllers\RestoreController::class] = new \SkyFi\Backup\Controllers\RestoreController(
            $this->instances[\SkyFi\Backup\Services\RestoreService::class]
        );
        $this->instances[\SkyFi\Backup\Controllers\StorageProviderController::class] = new \SkyFi\Backup\Controllers\StorageProviderController(
            $this->instances[\SkyFi\Backup\Repositories\PdoStorageProviderRepository::class]
        );
        $this->instances[\SkyFi\Backup\Controllers\DrPlanController::class] = new \SkyFi\Backup\Controllers\DrPlanController(
            $this->instances[\SkyFi\Backup\Repositories\PdoDrPlanRepository::class]
        );
        // ─── End Backup, Restore & Disaster Recovery Module ───────────────

        // ─── API Gateway, Webhooks & Third-Party Integrations Module ──────
        $this->instances[\SkyFi\Integration\Repositories\PdoClientApplicationRepository::class] = new \SkyFi\Integration\Repositories\PdoClientApplicationRepository($pdo);
        $this->instances[\SkyFi\Integration\Repositories\PdoApiKeyRepository::class] = new \SkyFi\Integration\Repositories\PdoApiKeyRepository($pdo);
        $this->instances[\SkyFi\Integration\Repositories\PdoWebhookRepository::class] = new \SkyFi\Integration\Repositories\PdoWebhookRepository($pdo);
        $this->instances[\SkyFi\Integration\Repositories\PdoWebhookDeliveryRepository::class] = new \SkyFi\Integration\Repositories\PdoWebhookDeliveryRepository($pdo);
        $this->instances[\SkyFi\Integration\Repositories\PdoEventRegistryRepository::class] = new \SkyFi\Integration\Repositories\PdoEventRegistryRepository($pdo);
        $this->instances[\SkyFi\Integration\Repositories\PdoConnectorRepository::class] = new \SkyFi\Integration\Repositories\PdoConnectorRepository($pdo);
        $this->instances[\SkyFi\Integration\Repositories\PdoRequestLogRepository::class] = new \SkyFi\Integration\Repositories\PdoRequestLogRepository($pdo);

        $this->instances[\SkyFi\Integration\Services\ApiKeyManager::class] = new \SkyFi\Integration\Services\ApiKeyManager();
        $this->instances[\SkyFi\Integration\Services\WebhookSignatureService::class] = new \SkyFi\Integration\Services\WebhookSignatureService();
        $this->instances[\SkyFi\Integration\Services\ConnectorRegistry::class] = new \SkyFi\Integration\Services\ConnectorRegistry();
        $this->instances[\SkyFi\Integration\Services\RateLimitService::class] = new \SkyFi\Integration\Services\RateLimitService($pdo);

        $this->instances[\SkyFi\Integration\Contracts\ApiKeyRepositoryContract::class] = $this->instances[\SkyFi\Integration\Repositories\PdoApiKeyRepository::class];
        $this->instances[\SkyFi\Integration\Contracts\WebhookRepositoryContract::class] = $this->instances[\SkyFi\Integration\Repositories\PdoWebhookRepository::class];
        $this->instances[\SkyFi\Integration\Contracts\WebhookDeliveryRepositoryContract::class] = $this->instances[\SkyFi\Integration\Repositories\PdoWebhookDeliveryRepository::class];
        $this->instances[\SkyFi\Integration\Contracts\EventRegistryRepositoryContract::class] = $this->instances[\SkyFi\Integration\Repositories\PdoEventRegistryRepository::class];
        $this->instances[\SkyFi\Integration\Contracts\ConnectorRepositoryContract::class] = $this->instances[\SkyFi\Integration\Repositories\PdoConnectorRepository::class];
        $this->instances[\SkyFi\Integration\Contracts\RequestLogRepositoryContract::class] = $this->instances[\SkyFi\Integration\Repositories\PdoRequestLogRepository::class];

        $this->instances[\SkyFi\Integration\Validators\ApiKeyValidator::class] = new \SkyFi\Integration\Validators\ApiKeyValidator();
        $this->instances[\SkyFi\Integration\Validators\ClientApplicationValidator::class] = new \SkyFi\Integration\Validators\ClientApplicationValidator();
        $this->instances[\SkyFi\Integration\Validators\WebhookValidator::class] = new \SkyFi\Integration\Validators\WebhookValidator();
        $this->instances[\SkyFi\Integration\Validators\ConnectorValidator::class] = new \SkyFi\Integration\Validators\ConnectorValidator();

        $this->instances[\SkyFi\Integration\Services\ApiKeyService::class] = new \SkyFi\Integration\Services\ApiKeyService(
            $this->instances[\SkyFi\Integration\Contracts\ApiKeyRepositoryContract::class],
            $this->instances[\SkyFi\Integration\Services\ApiKeyManager::class],
        );
        $this->instances[\SkyFi\Integration\Contracts\ApiKeyServiceContract::class] = $this->instances[\SkyFi\Integration\Services\ApiKeyService::class];

        $this->instances[\SkyFi\Integration\Services\ClientApplicationService::class] = new \SkyFi\Integration\Services\ClientApplicationService(
            $this->instances[\SkyFi\Integration\Repositories\PdoClientApplicationRepository::class],
        );

        $this->instances[\SkyFi\Integration\Services\WebhookDispatcher::class] = new \SkyFi\Integration\Services\WebhookDispatcher(
            $this->instances[\SkyFi\Integration\Contracts\WebhookRepositoryContract::class],
            $this->instances[\SkyFi\Integration\Contracts\WebhookDeliveryRepositoryContract::class],
            $this->instances[\SkyFi\Integration\Services\WebhookSignatureService::class],
        );
        $this->instances[\SkyFi\Integration\Contracts\WebhookDispatcherContract::class] = $this->instances[\SkyFi\Integration\Services\WebhookDispatcher::class];

        $this->instances[\SkyFi\Integration\Services\WebhookService::class] = new \SkyFi\Integration\Services\WebhookService(
            $this->instances[\SkyFi\Integration\Contracts\WebhookRepositoryContract::class],
        );

        $this->instances[\SkyFi\Integration\Services\EventRegistryService::class] = new \SkyFi\Integration\Services\EventRegistryService(
            $this->instances[\SkyFi\Integration\Contracts\EventRegistryRepositoryContract::class],
        );

        $this->instances[\SkyFi\Integration\Services\ConnectorService::class] = new \SkyFi\Integration\Services\ConnectorService(
            $this->instances[\SkyFi\Integration\Contracts\ConnectorRepositoryContract::class],
            $this->instances[\SkyFi\Integration\Services\ConnectorRegistry::class],
        );
        $this->instances[\SkyFi\Integration\Contracts\ConnectorServiceContract::class] = $this->instances[\SkyFi\Integration\Services\ConnectorService::class];

        $this->instances[\SkyFi\Integration\Services\RequestLogService::class] = new \SkyFi\Integration\Services\RequestLogService(
            $this->instances[\SkyFi\Integration\Contracts\RequestLogRepositoryContract::class],
        );

        $this->instances[\SkyFi\Integration\Services\IntegrationDashboardService::class] = new \SkyFi\Integration\Services\IntegrationDashboardService(
            $this->instances[\SkyFi\Integration\Contracts\ApiKeyRepositoryContract::class],
            $this->instances[\SkyFi\Integration\Contracts\WebhookRepositoryContract::class],
            $this->instances[\SkyFi\Integration\Contracts\WebhookDeliveryRepositoryContract::class],
            $this->instances[\SkyFi\Integration\Contracts\EventRegistryRepositoryContract::class],
            $this->instances[\SkyFi\Integration\Contracts\ConnectorRepositoryContract::class],
            $this->instances[\SkyFi\Integration\Contracts\RequestLogRepositoryContract::class],
        );
        $this->instances[\SkyFi\Integration\Contracts\IntegrationServiceContract::class] = $this->instances[\SkyFi\Integration\Services\IntegrationDashboardService::class];

        $this->instances[\SkyFi\Integration\Controllers\ApiKeyController::class] = new \SkyFi\Integration\Controllers\ApiKeyController(
            $this->instances[\SkyFi\Integration\Contracts\ApiKeyServiceContract::class],
            $this->instances[\SkyFi\Integration\Validators\ApiKeyValidator::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Integration\Controllers\ClientApplicationController::class] = new \SkyFi\Integration\Controllers\ClientApplicationController(
            $this->instances[\SkyFi\Integration\Services\ClientApplicationService::class],
            $this->instances[\SkyFi\Integration\Validators\ClientApplicationValidator::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Integration\Controllers\WebhookController::class] = new \SkyFi\Integration\Controllers\WebhookController(
            $this->instances[\SkyFi\Integration\Services\WebhookService::class],
            $this->instances[\SkyFi\Integration\Validators\WebhookValidator::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Integration\Controllers\WebhookDeliveryController::class] = new \SkyFi\Integration\Controllers\WebhookDeliveryController(
            $this->instances[\SkyFi\Integration\Contracts\WebhookDeliveryRepositoryContract::class],
            $this->instances[\SkyFi\Integration\Contracts\WebhookDispatcherContract::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Integration\Controllers\EventRegistryController::class] = new \SkyFi\Integration\Controllers\EventRegistryController(
            $this->instances[\SkyFi\Integration\Services\EventRegistryService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Integration\Controllers\ConnectorController::class] = new \SkyFi\Integration\Controllers\ConnectorController(
            $this->instances[\SkyFi\Integration\Contracts\ConnectorServiceContract::class],
            $this->instances[\SkyFi\Integration\Services\ConnectorRegistry::class],
            $this->instances[\SkyFi\Integration\Validators\ConnectorValidator::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Integration\Controllers\InboundWebhookController::class] = new \SkyFi\Integration\Controllers\InboundWebhookController(
            $this->instances[\SkyFi\Integration\Contracts\WebhookDispatcherContract::class],
        );
        $this->instances[\SkyFi\Integration\Controllers\RequestLogController::class] = new \SkyFi\Integration\Controllers\RequestLogController(
            $this->instances[\SkyFi\Integration\Services\RequestLogService::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Integration\Controllers\IntegrationDashboardController::class] = new \SkyFi\Integration\Controllers\IntegrationDashboardController(
            $this->instances[\SkyFi\Integration\Contracts\IntegrationServiceContract::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        // Register integration event subscriber
        $this->instances[\SkyFi\Integration\EventSubscribers\DomainEventSubscriber::class] = new \SkyFi\Integration\EventSubscribers\DomainEventSubscriber(
            $this->instances[\SkyFi\Integration\Contracts\WebhookDispatcherContract::class],
            $this->instances[\SkyFi\Integration\Contracts\EventRegistryRepositoryContract::class],
        );
        $this->instances[\SkyFi\Integration\EventSubscribers\DomainEventSubscriber::class]->register();
        // ─── End API Gateway, Webhooks & Integrations Module ─────────────

        // ─── Workflow Automation Engine Module ───────────────────────────
        $this->instances[\SkyFi\Workflow\Repositories\PdoWorkflowRepository::class] = new \SkyFi\Workflow\Repositories\PdoWorkflowRepository($pdo);
        $this->instances[\SkyFi\Workflow\Contracts\WorkflowRepositoryContract::class] = $this->instances[\SkyFi\Workflow\Repositories\PdoWorkflowRepository::class];
        $this->instances[\SkyFi\Workflow\Repositories\PdoWorkflowVersionRepository::class] = new \SkyFi\Workflow\Repositories\PdoWorkflowVersionRepository($pdo);
        $this->instances[\SkyFi\Workflow\Contracts\WorkflowVersionRepositoryContract::class] = $this->instances[\SkyFi\Workflow\Repositories\PdoWorkflowVersionRepository::class];
        $this->instances[\SkyFi\Workflow\Repositories\PdoWorkflowExecutionRepository::class] = new \SkyFi\Workflow\Repositories\PdoWorkflowExecutionRepository($pdo);
        $this->instances[\SkyFi\Workflow\Contracts\WorkflowExecutionRepositoryContract::class] = $this->instances[\SkyFi\Workflow\Repositories\PdoWorkflowExecutionRepository::class];

        $this->instances[\SkyFi\Workflow\Services\WorkflowCatalog::class] = new \SkyFi\Workflow\Services\WorkflowCatalog();
        $this->instances[\SkyFi\Workflow\Validators\WorkflowValidator::class] = new \SkyFi\Workflow\Validators\WorkflowValidator(
            $this->instances[\SkyFi\Workflow\Services\WorkflowCatalog::class],
        );
        $this->instances[\SkyFi\Workflow\Services\RuleEvaluator::class] = new \SkyFi\Workflow\Services\RuleEvaluator();
        $this->instances[\SkyFi\Workflow\Contracts\RuleEvaluatorContract::class] = $this->instances[\SkyFi\Workflow\Services\RuleEvaluator::class];

        $this->instances[\SkyFi\Workflow\Services\ActionDispatcher::class] = new \SkyFi\Workflow\Services\ActionDispatcher(
            $this->instances[\SkyFi\Notifications\Services\NotificationService::class],
            $this->instances[\SkyFi\Support\Services\TicketService::class],
            $this->instances[\SkyFi\FieldService\Services\WorkOrderService::class],
            $this->instances[InvoiceService::class],
            $this->instances[ConnectionService::class],
            $this->instances[\SkyFi\Pppoe\Services\PppoeService::class],
            $this->instances[CustomerService::class],
            $this->instances[\SkyFi\Integration\Contracts\WebhookDispatcherContract::class],
        );
        $this->instances[\SkyFi\Workflow\Contracts\ActionDispatcherContract::class] = $this->instances[\SkyFi\Workflow\Services\ActionDispatcher::class];

        $this->instances[\SkyFi\Workflow\Services\WorkflowEngine::class] = new \SkyFi\Workflow\Services\WorkflowEngine(
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowRepositoryContract::class],
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowVersionRepositoryContract::class],
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowExecutionRepositoryContract::class],
            $this->instances[\SkyFi\Workflow\Contracts\RuleEvaluatorContract::class],
            $this->instances[\SkyFi\Workflow\Contracts\ActionDispatcherContract::class],
        );
        $this->instances[\SkyFi\Workflow\Contracts\WorkflowEngineContract::class] = $this->instances[\SkyFi\Workflow\Services\WorkflowEngine::class];

        $this->instances[\SkyFi\Workflow\Services\WorkflowScheduler::class] = new \SkyFi\Workflow\Services\WorkflowScheduler(
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowExecutionRepositoryContract::class],
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowEngineContract::class],
        );

        $this->instances[\SkyFi\Workflow\Services\TriggerManager::class] = new \SkyFi\Workflow\Services\TriggerManager(
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowRepositoryContract::class],
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowVersionRepositoryContract::class],
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowEngineContract::class],
        );
        $this->instances[\SkyFi\Workflow\Contracts\TriggerManagerContract::class] = $this->instances[\SkyFi\Workflow\Services\TriggerManager::class];

        $this->instances[\SkyFi\Workflow\Services\WorkflowService::class] = new \SkyFi\Workflow\Services\WorkflowService(
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowRepositoryContract::class],
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowVersionRepositoryContract::class],
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowExecutionRepositoryContract::class],
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowEngineContract::class],
            $this->instances[\SkyFi\Workflow\Services\WorkflowScheduler::class],
            $this->instances[\SkyFi\Workflow\Services\WorkflowCatalog::class],
            $this->instances[\SkyFi\Workflow\Validators\WorkflowValidator::class],
            $this->instances[\SkyFi\Integration\Contracts\EventRegistryRepositoryContract::class],
            $pdo,
        );
        $this->instances[\SkyFi\Workflow\Contracts\WorkflowServiceContract::class] = $this->instances[\SkyFi\Workflow\Services\WorkflowService::class];

        $this->instances[\SkyFi\Workflow\Controllers\WorkflowController::class] = new \SkyFi\Workflow\Controllers\WorkflowController(
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowServiceContract::class],
            $this->instances[\SkyFi\Workflow\Validators\WorkflowValidator::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Workflow\Controllers\WorkflowExecutionController::class] = new \SkyFi\Workflow\Controllers\WorkflowExecutionController(
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowServiceContract::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Workflow\Controllers\WorkflowCatalogController::class] = new \SkyFi\Workflow\Controllers\WorkflowCatalogController(
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowServiceContract::class],
            $this->instances[\SkyFi\Workflow\Services\WorkflowCatalog::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Workflow\Controllers\WorkflowDashboardController::class] = new \SkyFi\Workflow\Controllers\WorkflowDashboardController(
            $this->instances[\SkyFi\Workflow\Contracts\WorkflowServiceContract::class],
            $this->instances[RequirePermissionMiddleware::class],
        );
        $this->instances[\SkyFi\Workflow\EventSubscribers\DomainEventSubscriber::class] = new \SkyFi\Workflow\EventSubscribers\DomainEventSubscriber(
            $this->instances[\SkyFi\Workflow\Contracts\TriggerManagerContract::class],
        );
        $this->instances[\SkyFi\Workflow\EventSubscribers\DomainEventSubscriber::class]->register();
        // ─── End Workflow Automation Engine Module ───────────────────────

        // ─── Customer Self-Service Portal Module ────────────────────────
        $this->instances[\SkyFi\Portal\Validators\PortalValidator::class] = new \SkyFi\Portal\Validators\PortalValidator();
        $this->instances[\SkyFi\Portal\Services\PortalService::class] = new \SkyFi\Portal\Services\PortalService(
            $this->instances[PdoUserRepository::class],
            $this->instances[CustomerService::class],
            $this->instances[ConnectionService::class],
            $this->instances[InvoiceService::class],
            $this->instances[PaymentService::class],
            $this->instances[\SkyFi\Support\Services\TicketService::class],
            $this->instances[\SkyFi\Notifications\Services\NotificationService::class],
            $this->instances[\SkyFi\Notifications\Services\PreferenceService::class],
            $this->instances[PackageService::class],
            $this->instances[\SkyFi\Portal\Validators\PortalValidator::class],
            $pdo,
        );
        $this->instances[\SkyFi\Portal\Contracts\PortalServiceContract::class] = $this->instances[\SkyFi\Portal\Services\PortalService::class];
        $this->instances[\SkyFi\Portal\Controllers\PortalController::class] = new \SkyFi\Portal\Controllers\PortalController(
            $this->instances[\SkyFi\Portal\Services\PortalService::class],
            $permission,
        );
        // ─── End Customer Self-Service Portal Module ────────────────────

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
