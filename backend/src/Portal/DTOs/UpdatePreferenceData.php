<?php

declare(strict_types=1);

namespace SkyFi\Portal\DTOs;

final class UpdatePreferenceData
{
    /**
     * @param list<array<string, mixed>> $preferences
     */
    public function __construct(public readonly array $preferences)
    {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $rows = $data['preferences'] ?? ($data['data']['attributes']['preferences'] ?? []);
        if (!is_array($rows)) {
            $rows = [];
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized[] = [
                'channel' => (string) ($row['channel'] ?? ''),
                'category' => (string) ($row['category'] ?? '*'),
                'is_enabled' => (int) (bool) ($row['is_enabled'] ?? true),
                'quiet_hours_start' => $row['quiet_hours_start'] ?? null,
                'quiet_hours_end' => $row['quiet_hours_end'] ?? null,
                'quiet_hours_timezone' => $row['quiet_hours_timezone'] ?? null,
            ];
        }

        return new self($normalized);
    }
}
