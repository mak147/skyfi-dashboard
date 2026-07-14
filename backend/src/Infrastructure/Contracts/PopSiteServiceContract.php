<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Contracts;

use SkyFi\Infrastructure\Data\CreatePopSiteData;
use SkyFi\Infrastructure\Data\PopSiteListFilters;
use SkyFi\Infrastructure\Data\UpdatePopSiteData;
use SkyFi\Infrastructure\Models\PopSite;

interface PopSiteServiceContract
{
    /** List POP sites with filtering, sorting, and pagination. */
    public function list(PopSiteListFilters $filters): array;

    /** Get a POP site by ID. */
    public function get(int $id): PopSite;

    /** Create a new POP site. */
    public function create(CreatePopSiteData $data, int $authUserId, ?string $ip, ?string $ua): PopSite;

    /** Update a POP site. */
    public function update(int $id, UpdatePopSiteData $data, int $authUserId, ?string $ip, ?string $ua): PopSite;

    /** Soft delete a POP site. */
    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void;

    /** Change POP site status. */
    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): PopSite;

    /** Get towers for a POP site. */
    public function getTowers(int $popSiteId): array;

    /** Get map points for all POP sites. */
    public function getMapPoints(): array;
}