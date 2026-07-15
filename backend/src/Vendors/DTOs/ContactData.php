<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class ContactData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $department,
        public readonly ?string $jobTitle,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly bool $isPrimary,
        public readonly bool $isEmergency,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $text = static fn(string $key): ?string => isset($data[$key]) && trim((string) $data[$key]) !== '' ? trim((string) $data[$key]) : null;
        return new self(
            trim((string) ($data['name'] ?? '')),
            $text('department'),
            $text('job_title'),
            $text('phone'),
            $text('email'),
            filter_var($data['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN),
            filter_var($data['is_emergency'] ?? false, FILTER_VALIDATE_BOOLEAN),
            $text('notes'),
        );
    }
}
