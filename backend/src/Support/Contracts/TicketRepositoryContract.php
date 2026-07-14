<?php

declare(strict_types=1);
namespace SkyFi\Support\Contracts;
use SkyFi\Support\DomainModels\SupportTicket;
use SkyFi\Support\DomainModels\SlaPolicy;
use SkyFi\Support\DTOs\TicketListFilters;
interface TicketRepositoryContract
{
    /** @return array{items:array<int,SupportTicket>,total:int,page:int,perPage:int,lastPage:int} */ public function list(
        TicketListFilters $filters,
    ): array;
    public function find(int $id, bool $forUpdate = false): ?SupportTicket;
    /** @param array<string,mixed> $data */ public function create(
        array $data,
        int $actorId,
        SlaPolicy $policy,
        ?int $parentId = null,
    ): SupportTicket;
    /** @param array<string,mixed> $data */ public function update(
        int $id,
        array $data,
        int $actorId,
    ): SupportTicket;
    public function softDelete(int $id, int $actorId): void;
    public function findSlaPolicy(
        int $categoryId,
        string $priority,
    ): ?SlaPolicy;
    /** @return array<int,array<string,mixed>> */ public function categories(): array;
    /** @return array<int,array<string,mixed>> */ public function teams(): array;
    /** @return array<int,array<string,mixed>> */ public function slaPolicies(): array;
    /** @return array<string,mixed> */ public function dashboard(): array;
    /** @return array<string,mixed> */ public function slaDashboard(): array;
    public function processBreaches(?int $actorId = null): int;
    /** @param array<string,mixed>|null $metadata */ public function history(
        int $ticketId,
        string $event,
        ?int $actorId,
        string $description,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?array $metadata = null,
    ): void;
    /** @return array<int,array<string,mixed>> */ public function timeline(
        int $ticketId,
    ): array;
    /** @return array<int,array<string,mixed>> */ public function assignments(
        int $ticketId,
    ): array;
    /** @return array<string,mixed> */ public function context(
        int $ticketId,
    ): array;
    /** @return array<int,array<string,mixed>> */ public function lookup(
        string $resource,
        string $search,
        ?int $customerId = null,
    ): array;
    /** @param array<string,mixed> $data @return array<string,mixed> */ public function saveConfiguration(
        string $resource,
        ?int $id,
        array $data,
        int $actorId,
    ): array;
    public function deleteConfiguration(
        string $resource,
        int $id,
        int $actorId,
    ): void;
    /** @param array<string,mixed> $data @return array<int,array<string,mixed>> */ public function validateContext(
        array $data,
    ): array;
    public function transaction(callable $callback): mixed;
}
