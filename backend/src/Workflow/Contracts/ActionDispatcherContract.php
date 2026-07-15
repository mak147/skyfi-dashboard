<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Contracts;

interface ActionDispatcherContract
{
    /**
     * @param array<string, mixed> $action
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function dispatch(array $action, array $payload, ?int $actorUserId, bool $dryRun = false): array;
}
