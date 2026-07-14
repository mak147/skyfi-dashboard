<?php

declare(strict_types=1);

namespace SkyFi\Customers\Contracts;

use SkyFi\Customers\Data\CreateCustomerData;
use SkyFi\Customers\Data\CustomerListFilters;
use SkyFi\Customers\Data\UpdateCustomerData;
use SkyFi\Customers\Models\Customer;

interface CustomerServiceContract
{
    /**
     * List customers with filtering, sorting, and pagination.
     *
     * @return array{items: array<Customer>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function list(CustomerListFilters $filters): array;

    /** Get a single customer by ID. */
    public function get(int $id): Customer;

    /** Create a new customer. */
    public function create(CreateCustomerData $data, int $authUserId, ?string $ip, ?string $ua): Customer;

    /** Update an existing customer. */
    public function update(int $id, UpdateCustomerData $data, int $authUserId, ?string $ip, ?string $ua): Customer;

    /** Soft-delete a customer. */
    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void;

    /** Change customer lifecycle status. */
    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): Customer;
}
