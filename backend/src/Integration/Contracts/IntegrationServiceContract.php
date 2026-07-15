<?php

declare(strict_types=1);

namespace SkyFi\Integration\Contracts;

interface IntegrationServiceContract
{
    /** @return array<string, mixed> */
    public function dashboard(): array;
}
