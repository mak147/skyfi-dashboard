<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

use SkyFi\Integration\Contracts\ConnectorRepositoryContract;
use SkyFi\Integration\Contracts\ConnectorServiceContract;
use SkyFi\Integration\DomainModels\ConnectorConfiguration;
use SkyFi\Integration\DTOs\UpdateConnectorData;
use SkyFi\Shared\Exceptions\NotFoundException;

final class ConnectorService implements ConnectorServiceContract
{
    public function __construct(
        private readonly ConnectorRepositoryContract $connectors,
        private readonly ConnectorRegistry $registry,
    ) {}

    /** @return list<ConnectorConfiguration> */
    public function list(): array
    {
        return $this->connectors->listAll();
    }

    public function get(string $connectorType): ConnectorConfiguration
    {
        return $this->connectors->findByType($connectorType)
            ?? throw new NotFoundException("Connector '{$connectorType}' not found.");
    }

    public function update(string $connectorType, int $userId, UpdateConnectorData $data): ConnectorConfiguration
    {
        $existing = $this->get($connectorType);
        $updateData = [];
        if ($data->name !== null) {
            $updateData['name'] = $data->name;
        }
        if ($data->description !== null) {
            $updateData['description'] = $data->description;
        }
        if ($data->config !== null) {
            // Merge config rather than replace — keep existing values for omitted fields
            $existingConfig = $existing->toArrayFull()['config'] ?? [];
            $updateData['config'] = array_merge($existingConfig, $data->config);
        }
        if ($data->isEnabled !== null) {
            $updateData['is_enabled'] = $data->isEnabled;
        }
        if ($data->rateLimitPerMinute !== null) {
            $updateData['rate_limit_per_minute'] = $data->rateLimitPerMinute;
        }
        $updateData['created_by'] = $userId;

        return $this->connectors->update($existing->id(), $updateData)
            ?? throw new NotFoundException("Connector not found after update.");
    }

    public function test(string $connectorType): array
    {
        $config = $this->get($connectorType);
        $connector = $this->registry->get($connectorType);
        if ($connector === null) {
            return ['success' => false, 'message' => 'No connector implementation found.'];
        }

        return $connector->test($config->toArrayFull()['config'] ?? []);
    }
}
