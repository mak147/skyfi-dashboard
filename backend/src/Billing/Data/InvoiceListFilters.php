<?php

declare(strict_types=1);

namespace SkyFi\Billing\Data;

final class InvoiceListFilters
{
    public function __construct(
        public readonly ?string $status,
        public readonly ?int $customerId,
        public readonly ?string $dueFrom,
        public readonly ?string $dueTo,
        public readonly ?string $search,
        public readonly int $page,
        public readonly int $perPage,
        public readonly string $sort,
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $page = 1;
        $perPage = 15;

        if (isset($query['page']) && is_array($query['page'])) {
            if (isset($query['page']['number']) && is_numeric($query['page']['number'])) {
                $page = max(1, (int) $query['page']['number']);
            }
            if (isset($query['page']['size']) && is_numeric($query['page']['size'])) {
                $perPage = max(1, min(100, (int) $query['page']['size']));
            }
        }

        $sort = isset($query['sort']) && is_string($query['sort']) ? $query['sort'] : '-created_at';

        $status = null;
        $customerId = null;
        $dueFrom = null;
        $dueTo = null;
        $search = null;

        if (isset($query['filter']) && is_array($query['filter'])) {
            $filter = $query['filter'];
            if (isset($filter['status']) && is_string($filter['status']) && $filter['status'] !== '') {
                $status = $filter['status'];
            }
            if (isset($filter['customer_id']) && is_numeric($filter['customer_id'])) {
                $customerId = (int) $filter['customer_id'];
            }
            if (isset($filter['due_from']) && is_string($filter['due_from']) && $filter['due_from'] !== '') {
                $dueFrom = $filter['due_from'];
            }
            if (isset($filter['due_to']) && is_string($filter['due_to']) && $filter['due_to'] !== '') {
                $dueTo = $filter['due_to'];
            }
            if (isset($filter['search']) && is_string($filter['search']) && $filter['search'] !== '') {
                $search = $filter['search'];
            }
        }

        return new self(
            status: $status,
            customerId: $customerId,
            dueFrom: $dueFrom,
            dueTo: $dueTo,
            search: $search,
            page: $page,
            perPage: $perPage,
            sort: $sort,
        );
    }
}
