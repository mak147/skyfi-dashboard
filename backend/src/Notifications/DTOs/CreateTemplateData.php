<?php

declare(strict_types=1);

namespace SkyFi\Notifications\DTOs;

final class CreateTemplateData
{
    /**
     * @param list<string>|null $variables
     */
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $category,
        public readonly string $channel,
        public readonly string $bodyTemplate,
        public readonly ?string $subjectTemplate = null,
        public readonly string $locale = 'en',
        public readonly bool $isTransactional = false,
        public readonly bool $isActive = true,
        public readonly ?array $variables = null,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $a = $input['data']['attributes'] ?? $input;

        return new self(
            code: trim((string) ($a['code'] ?? '')),
            name: trim((string) ($a['name'] ?? '')),
            category: trim((string) ($a['category'] ?? '')),
            channel: trim((string) ($a['channel'] ?? 'in_app')),
            bodyTemplate: (string) ($a['body_template'] ?? ''),
            subjectTemplate: isset($a['subject_template']) ? (string) $a['subject_template'] : null,
            locale: trim((string) ($a['locale'] ?? 'en')) ?: 'en',
            isTransactional: (bool) ($a['is_transactional'] ?? false),
            isActive: (bool) ($a['is_active'] ?? true),
            variables: isset($a['variables']) && is_array($a['variables']) ? array_values(array_map('strval', $a['variables'])) : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'channel' => $this->channel,
            'body_template' => $this->bodyTemplate,
            'subject_template' => $this->subjectTemplate,
            'locale' => $this->locale,
            'is_transactional' => $this->isTransactional ? 1 : 0,
            'is_active' => $this->isActive ? 1 : 0,
            'variables' => $this->variables ?? [],
        ];
    }
}
