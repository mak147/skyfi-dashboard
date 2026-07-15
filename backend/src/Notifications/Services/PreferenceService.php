<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Services;

use SkyFi\Notifications\Contracts\UserPreferenceRepositoryContract;
use SkyFi\Notifications\DTOs\UserPreferenceData;
use SkyFi\Notifications\Validators\PreferenceValidator;

final class PreferenceService
{
    public function __construct(
        private readonly UserPreferenceRepositoryContract $preferences,
        private readonly PreferenceValidator $validator,
        private readonly NotificationCatalog $catalog,
    ) {}

    /** @return array<string, mixed> */
    public function get(int $userId): array
    {
        $rows = array_map(
            static fn ($p) => $p->toArray(),
            $this->preferences->listForUser($userId)
        );

        if ($rows === []) {
            // Sensible defaults for UI
            foreach ($this->catalog->channels() as $channel) {
                $rows[] = [
                    'user_id' => $userId,
                    'channel' => $channel,
                    'category' => '*',
                    'is_enabled' => in_array($channel, ['in_app', 'email'], true) ? 1 : 0,
                    'quiet_hours_start' => null,
                    'quiet_hours_end' => null,
                    'quiet_hours_timezone' => 'Asia/Karachi',
                ];
            }
        }

        return [
            'user_id' => $userId,
            'preferences' => $rows,
            'categories' => $this->catalog->categories(),
            'channels' => $this->catalog->channels(),
        ];
    }

    /** @return array<string, mixed> */
    public function update(int $userId, UserPreferenceData $data): array
    {
        $this->validator->update($data);
        $saved = $this->preferences->replaceForUser($userId, $data->preferences);

        return [
            'user_id' => $userId,
            'preferences' => array_map(static fn ($p) => $p->toArray(), $saved),
            'categories' => $this->catalog->categories(),
            'channels' => $this->catalog->channels(),
        ];
    }
}
