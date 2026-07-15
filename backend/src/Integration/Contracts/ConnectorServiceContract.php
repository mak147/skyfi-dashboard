<?php

declare(strict_types=1);

namespace SkyFi\Integration\Contracts;

use SkyFi\Integration\DomainModels\ConnectorConfiguration;
use SkyFi\Integration\DTOs\UpdateConnectorData;

interface ConnectorServiceContract
{
    /** @return list<ConnectorConfiguration> */
    public function list(): array;

    public function get(string $connectorType): ConnectorConfiguration;

    public function update(string $connectorType, int $userId, UpdateConnectorData $data): ConnectorConfiguration;

    /** @return array{success: bool, message: string} */
    public function test(string $connectorType): array;
}
