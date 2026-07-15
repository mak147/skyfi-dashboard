<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Contracts;
use SkyFi\Vendors\DomainModels\SupplierContact;
use SkyFi\Vendors\DTOs\ContactData;
use SkyFi\Vendors\DTOs\ContactListFilters;
interface SupplierContactRepositoryContract
{
    /** @return array{items: array<int, SupplierContact>, total: int, page: int, perPage: int, lastPage: int} */ public function list(ContactListFilters $filters): array;
    public function find(int $id): ?SupplierContact;
    public function create(int $vendorId, ContactData $data, int $actorId): SupplierContact;
    public function update(int $id, ContactData $data, int $actorId): SupplierContact;
    public function delete(int $id, int $actorId): void;
    public function designate(int $id, string $type, int $actorId): SupplierContact;
    public function upsertPrimary(int $vendorId, string $name, ?string $phone, ?string $email, int $actorId): SupplierContact;
}
