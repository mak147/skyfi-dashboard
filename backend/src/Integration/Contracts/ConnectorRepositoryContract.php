<?php

declare(strict_types=1);

namespace SkyFi\Integration\Contracts;

use SkyFi\Integration\DomainModels\ConnectorConfiguration;

interface ConnectorRepositoryContract
{
    /** @return list<ConnectorConfiguration> */
    public function listAll(): array;

    public function findByType(string $connectorType): ?ConnectorConfiguration;

    public function create(array $data): ConnectorConfiguration;

    public function update(int $id, array $data): ?ConnectorConfiguration;
}
