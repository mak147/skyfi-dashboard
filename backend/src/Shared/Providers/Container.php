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
