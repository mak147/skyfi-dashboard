<?php

declare(strict_types=1);
namespace SkyFi\Support\Services;
use SkyFi\Support\Contracts\{
    SupportDashboardServiceContract,
    TicketRepositoryContract,
};
final class SupportDashboardService implements SupportDashboardServiceContract
{
    public function __construct(
        private readonly TicketRepositoryContract $repo,
    ) {}
    public function dashboard(): array
    {
        $this->repo->processBreaches();
        return $this->repo->dashboard();
    }
    public function slaDashboard(): array
    {
        $this->repo->processBreaches();
        return $this->repo->slaDashboard();
    }
    public function process(?int $actorId = null): int
    {
        return $this->repo->processBreaches($actorId);
    }
}
