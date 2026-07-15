<?php

declare(strict_types=1);

namespace SkyFi\Notifications\DTOs;

final class UserPreferenceData
{
    /**
     * @param list<array<string, mixed>> $preferences
     */
    public function __construct(public readonly array $preferences) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $a = $input['data']['attributes'] ?? $input;
        $rows = $a['preferences'] ?? $a;
        if (!is_array($rows)) {
            $rows = [];
        }
        // Support either { preferences: [...] } or a bare list
        if ($rows !== [] && array_is_list($rows) === false && isset($rows['channel'])) {
            $rows = [$rows];
        }
        if ($rows !== [] && array_is_list($rows) === false && !isset($rows[0])) {
            // map form: { email: { enabled, categories }, ... } not used; force empty
            $rows = $a['preferences'] ?? [];
        }

        $normalized = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
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
