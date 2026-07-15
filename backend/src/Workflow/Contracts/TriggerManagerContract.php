<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Contracts;

interface TriggerManagerContract
{
    public function register(): void;

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(string $eventKey, array $payload): int;
}
