<?php

declare(strict_types=1);

namespace SkyFi\Notifications\DTOs;

final class UpdateTemplateData
{
    /** @param array<string, mixed> $values */
    public function __construct(public readonly array $values) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $a = $input['data']['attributes'] ?? $input;
        $allowed = [
            'code', 'name', 'category', 'channel', 'subject_template', 'body_template',
            'locale', 'is_transactional', 'is_active', 'variables',
        ];
        $values = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $a)) {
                $values[$key] = $a[$key];
            }
        }
        if (isset($values['is_transactional'])) {
            $values['is_transactional'] = (int) (bool) $values['is_transactional'];
        }
        if (isset($values['is_active'])) {
            $values['is_active'] = (int) (bool) $values['is_active'];
        }

        return new self($values);
    }
}
