<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\DTOs;

use SkyFi\Mikrotik\DomainModels\RouterConnectionData;

final class ConnectionTestData
{
    public function __construct(
        public readonly string $host,
        public readonly int $apiPort,
        public readonly string $apiUsername,
        public readonly string $apiPassword,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            host: trim((string) ($data['host'] ?? '')),
            apiPort: isset($data['api_port']) ? (int) $data['api_port'] : 8729,
            apiUsername: trim((string) ($data['api_username'] ?? '')),
            apiPassword: (string) ($data['api_password'] ?? ''),
        );
    }

    public function toConnectionData(): RouterConnectionData
    {
        return new RouterConnectionData($this->host, $this->apiPort, $this->apiUsername, $this->apiPassword);
    }
}
