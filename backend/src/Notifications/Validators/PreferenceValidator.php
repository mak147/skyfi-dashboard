<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Validators;

use SkyFi\Notifications\DTOs\UserPreferenceData;
use SkyFi\Notifications\Services\NotificationCatalog;
use SkyFi\Shared\Exceptions\ValidationException;

final class PreferenceValidator
{
    public function __construct(private readonly NotificationCatalog $catalog) {}

    public function update(UserPreferenceData $data): void
    {
        $errors = [];
        $categories = array_merge(['*'], $this->catalog->categories());
        foreach ($data->preferences as $i => $row) {
            if (!in_array((string) ($row['channel'] ?? ''), $this->catalog->channels(), true)) {
                $errors[] = ['code' => 'invalid_channel', 'detail' => 'Invalid preference channel.', 'source' => ['pointer' => "/data/attributes/preferences/{$i}/channel"]];
            }
            if (!in_array((string) ($row['category'] ?? '*'), $categories, true)) {
                $errors[] = ['code' => 'invalid_category', 'detail' => 'Invalid preference category.', 'source' => ['pointer' => "/data/attributes/preferences/{$i}/category"]];
            }
            $start = $row['quiet_hours_start'] ?? null;
            $end = $row['quiet_hours_end'] ?? null;
            if (($start !== null && $start !== '') || ($end !== null && $end !== '')) {
                if (!$this->isTime($start) || !$this->isTime($end)) {
                    $errors[] = ['code' => 'invalid_quiet_hours', 'detail' => 'Quiet hours must be valid HH:MM or HH:MM:SS values.', 'source' => ['pointer' => "/data/attributes/preferences/{$i}/quiet_hours_start"]];
                }
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    private function isTime(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        return (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value);
    }
}
