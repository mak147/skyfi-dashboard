<?php

declare(strict_types=1);

namespace SkyFi\Audit\Contracts;

use SkyFi\Audit\DomainModels\ActivityEvent;
use SkyFi\Audit\DTOs\ActivityFilters;

interface ActivityRepositoryContract
{
    /** @return array{items: list<ActivityEvent>, page: int, perPage: int, total: int, lastPage: int} */
    public function search(ActivityFilters $filters): array;

    /** @param array<string, mixed> $data */
    public function create(array $data): ActivityEvent;
}
