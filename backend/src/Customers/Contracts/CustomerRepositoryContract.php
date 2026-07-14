<?php

declare(strict_types=1);

namespace SkyFi\Customers\Contracts;

use SkyFi\Customers\Data\CustomerListFilters;
use SkyFi\Customers\Models\Customer;

interface CustomerRepositoryContract
{
    /** Find a customer by ID, or null if not found (including soft-deleted). */
    public function find(int $id): ?Customer;

    /** Find a customer by ID that is not soft-deleted. */
    public function findActive(int $id): ?Customer;

    /** Check if a customer code already exists. */
    public function codeExists(string $code, ?int $excludeId = null): bool;

    /** Check if a CNIC already exists. */
    public function cnicExists(string $cnic, ?int $excludeId = null): bool;

    /**
     * List customers with filtering, sorting, and pagination.
     *
     * @return array{items: array<Customer>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function list(CustomerListFilters $filters): array;

    /** Create a new customer and return the created model. */
    public function create(array $data): Customer;

    /** Update a customer and return the updated model. */
    public function update(int $id, array $data): Customer;

    /** Soft-delete a customer. */
    public function softDelete(int $id): void;

    /** Update only the status field. */
    public function updateStatus(int $id, string $status): void;
}
