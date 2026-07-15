<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

use SkyFi\Integration\Contracts\EventRegistryRepositoryContract;
use SkyFi\Integration\DomainModels\EventRegistryEntry;
use SkyFi\Shared\Exceptions\NotFoundException;

final class EventRegistryService
{
    public function __construct(
        private readonly EventRegistryRepositoryContract $events,
    ) {}

    /** @return array{items: list<EventRegistryEntry>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(int $page = 1, int $perPage = 25, ?string $sourceModule = null): array
    {
        return $this->events->list($page, $perPage, $sourceModule);
    }

    public function get(int $id): EventRegistryEntry
    {
        return $this->events->find($id)
            ?? throw new NotFoundException('Event not found.');
    }

    /** @return list<string> */
    public function allActiveKeys(): array
    {
        return $this->events->allActiveKeys();
    }

    /** @return list<string> */
    public function sourceModules(): array
    {
        return $this->events->sourceModules();
    }
}
