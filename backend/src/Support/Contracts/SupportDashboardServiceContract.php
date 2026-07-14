<?php

declare(strict_types=1);
namespace SkyFi\Support\Contracts;
interface SupportDashboardServiceContract
{
    /** @return array<string,mixed> */ public function dashboard(): array;
    /** @return array<string,mixed> */ public function slaDashboard(): array;
    public function process(?int $actorId = null): int;
}
