<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Contracts;

interface PppoeSyncLoggerContract
{
    /** @param array<string, mixed>|null $diffPayload */
    public function log(
        int $routerId,
        ?int $accountId,
        string $action,
        string $status,
        string $message,
        ?array $diffPayload = null,
        ?int $createdBy = null
    ): void;

    /** @return array<int, array<string, mixed>> */
    public function listRecent(int $limit = 50, ?int $routerId = null, ?int $accountId = null): array;
}
