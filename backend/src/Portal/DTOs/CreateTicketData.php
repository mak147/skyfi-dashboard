<?php

declare(strict_types=1);

namespace SkyFi\Portal\DTOs;

final class CreateTicketData
{
    public function __construct(
        public readonly int $categoryId,
        public readonly string $priority,
        public readonly string $subject,
        public readonly string $description,
        public readonly ?int $connectionId,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['category_id'] ?? 0),
            (string) ($data['priority'] ?? 'normal'),
            trim((string) ($data['subject'] ?? '')),
            trim((string) ($data['description'] ?? '')),
            isset($data['connection_id']) && $data['connection_id'] !== '' ? (int) $data['connection_id'] : null,
        );
    }
}
