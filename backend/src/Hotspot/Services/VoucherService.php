<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Services;

use SkyFi\Hotspot\Contracts\HotspotProfileRepositoryContract;
use SkyFi\Hotspot\Contracts\HotspotSyncLoggerContract;
use SkyFi\Hotspot\Contracts\HotspotUserRepositoryContract;
use SkyFi\Hotspot\Contracts\VoucherBatchRepositoryContract;
use SkyFi\Hotspot\Contracts\VoucherRepositoryContract;
use SkyFi\Hotspot\Contracts\VoucherServiceContract;
use SkyFi\Hotspot\DomainModels\Voucher;
use SkyFi\Hotspot\DomainModels\VoucherBatch;
use SkyFi\Hotspot\DTOs\GenerateVoucherBatchData;
use SkyFi\Hotspot\DTOs\VoucherListFilters;
use SkyFi\Hotspot\Validators\VoucherValidator;
use SkyFi\Mikrotik\Contracts\CredentialCipherContract;
use SkyFi\Mikrotik\Contracts\MikrotikConnectionPoolContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class VoucherService implements VoucherServiceContract
{
    public function __construct(
        private readonly VoucherRepositoryContract $vouchers,
        private readonly VoucherBatchRepositoryContract $batches,
        private readonly HotspotUserRepositoryContract $users,
        private readonly HotspotProfileRepositoryContract $profiles,
        private readonly RouterServiceContract $routerService,
        private readonly MikrotikConnectionPoolContract $pool,
        private readonly CredentialCipherContract $cipher,
        private readonly HotspotSyncLoggerContract $syncLogger,
        private readonly VoucherValidator $validator,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function listVouchers(VoucherListFilters $filters): array
    {
        return $this->vouchers->list($filters);
    }

    public function listBatches(int $page = 1, int $perPage = 15, ?string $status = null): array
    {
        $result = $this->batches->list($page, $perPage, $status);

        $enrichedItems = [];
        foreach ($result['items'] as $batch) {
            $enrichedItems[] = $this->enrichBatchInfo($batch);
        }

        return [
            ...$result,
            'items' => $enrichedItems,
        ];
    }

    public function getVoucher(int $id): Voucher
    {
        return $this->vouchers->find($id) ?? throw new NotFoundException('Voucher not found.');
    }

    public function generateBatch(GenerateVoucherBatchData $data, int $actorId, ?string $ip, ?string $userAgent): VoucherBatch
    {
        $this->validator->validateGenerateBatch($data);

        $profile = $this->profiles->find($data->hotspotProfileId) ?? throw new ValidationException([[
            'code' => 'not_found',
            'detail' => 'Selected hotspot profile does not exist.',
            'source' => ['pointer' => '/data/attributes/hotspot_profile_id'],
        ]]);

        $this->routerService->get($data->routerId);

        $batchCode = $this->generateBatchCode();

        $expiresAt = null;
        if ($data->validityDays !== null && $data->validityDays > 0) {
            $expiresAt = gmdate('Y-m-d H:i:s', time() + ($data->validityDays * 86400));
        }

        $batch = $this->batches->insert([
            'batch_code' => $batchCode,
            'hotspot_profile_id' => $data->hotspotProfileId,
            'router_id' => $data->routerId,
            'quantity' => $data->quantity,
            'prefix' => $data->prefix,
            'price_per_voucher' => $data->pricePerVoucher,
            'time_limit' => $data->timeLimit,
            'data_limit_mb' => $data->dataLimitMb,
            'validity_days' => $data->validityDays,
            'status' => 'active',
            'generated_by' => $actorId,
            'notes' => $data->notes,
        ]);

        $prefix = $data->prefix ?? '';
        for ($i = 0; $i < $data->quantity; $i++) {
            $code = $this->generateVoucherCode($prefix);

            // Ensure uniqueness
            $attempts = 0;
            while ($this->vouchers->findByCode($code) !== null && $attempts < 10) {
                $code = $this->generateVoucherCode($prefix);
                $attempts++;
            }

            $this->vouchers->insert([
                'code' => $code,
                'batch_id' => $batch->id(),
                'status' => 'new',
                'time_limit' => $data->timeLimit,
                'data_limit_mb' => $data->dataLimitMb,
                'price' => $data->pricePerVoucher,
                'expires_at' => $expiresAt,
            ]);
        }

        $this->auditLogger->log($actorId, 'generate_batch', 'voucher_batch', $batch->id(), null, $batch->toArray(), $ip, $userAgent);

        return $this->enrichBatchInfo($batch);
    }

    public function revokeVoucher(int $id, int $actorId, ?string $ip, ?string $userAgent): Voucher
    {
        $voucher = $this->vouchers->find($id) ?? throw new NotFoundException('Voucher not found.');

        if ($voucher->status() !== 'new') {
            throw new ValidationException([[
                'code' => 'invalid_state',
                'detail' => 'Only unused vouchers can be revoked.',
                'source' => ['pointer' => '/data/attributes/status'],
            ]]);
        }

        $updated = $this->vouchers->update($id, ['status' => 'revoked']);

        $this->auditLogger->log($actorId, 'revoke', 'voucher', $id, $voucher->toArray(), $updated->toArray(), $ip, $userAgent);

        return $updated;
    }

    /** @return array<int, array<string, mixed>> */
    public function printVouchers(int $batchId): array
    {
        $batch = $this->batches->find($batchId) ?? throw new NotFoundException('Voucher batch not found.');

        $filters = new VoucherListFilters(perPage: 1000, batchId: $batchId);
        $result = $this->vouchers->list($filters);

        $vouchers = [];
        foreach ($result['items'] as $voucher) {
            $vouchers[] = [
                'code' => $voucher->code(),
                'status' => $voucher->status(),
                'time_limit' => $voucher->timeLimit(),
                'data_limit_mb' => $voucher->dataLimitMb(),
                'price' => $voucher->price(),
                'expires_at' => $voucher->expiresAt(),
                'qr_placeholder' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="50" x="10" font-size="10">' . htmlspecialchars($voucher->code()) . '</text></svg>'),
            ];
        }

        return [
            'batch' => $batch->toArray(),
            'vouchers' => $vouchers,
        ];
    }

    /** @return array<string, int> */
    public function getVoucherStats(): array
    {
        return [
            'total_new' => $this->vouchers->countByStatus('new'),
            'total_used' => $this->vouchers->countByStatus('used'),
            'total_expired' => $this->vouchers->countExpired(),
            'total_revoked' => $this->vouchers->countByStatus('revoked'),
            'daily_logins' => $this->vouchers->countDailyLogins(),
        ];
    }

    private function generateVoucherCode(string $prefix = ''): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = $prefix;
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }

    private function generateBatchCode(): string
    {
        return 'BTH-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    private function enrichBatchInfo(VoucherBatch $batch): VoucherBatch
    {
        $attributes = $batch->toArray();

        try {
            $router = $this->routerService->get($batch->routerId());
            $attributes['router_name'] = $router->toArray()['name'] ?? 'Unknown Router';
        } catch (\Throwable) {
            $attributes['router_name'] = 'Router #' . $batch->routerId();
        }

        try {
            $profile = $this->profiles->find($batch->hotspotProfileId());
            $attributes['profile_name'] = $profile?->name() ?? 'Profile #' . $batch->hotspotProfileId();
        } catch (\Throwable) {
            $attributes['profile_name'] = 'Profile #' . $batch->hotspotProfileId();
        }

        return VoucherBatch::fromRow($attributes);
    }
}
