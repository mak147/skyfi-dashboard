<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Services;

use SkyFi\Notifications\Contracts\NotificationTemplateRepositoryContract;
use SkyFi\Notifications\DomainModels\NotificationTemplate;
use SkyFi\Notifications\DTOs\CreateTemplateData;
use SkyFi\Notifications\DTOs\UpdateTemplateData;
use SkyFi\Notifications\Validators\TemplateValidator;
use SkyFi\Shared\Exceptions\NotFoundException;

final class TemplateService
{
    public function __construct(
        private readonly NotificationTemplateRepositoryContract $templates,
        private readonly TemplateValidator $validator,
        private readonly DeliveryService $delivery,
    ) {}

    /** @param array<string, mixed> $filters */
    public function list(array $filters = []): array
    {
        return $this->templates->list($filters);
    }

    public function get(int $id): NotificationTemplate
    {
        return $this->templates->find($id) ?? throw new NotFoundException('Notification template not found.');
    }

    public function create(CreateTemplateData $data, int $actorId): NotificationTemplate
    {
        $this->validator->create($data);

        return $this->templates->create($data->toArray(), $actorId);
    }

    public function update(int $id, UpdateTemplateData $data, int $actorId): NotificationTemplate
    {
        $this->validator->update($data->values);

        return $this->templates->update($id, $data->values, $actorId);
    }

    public function delete(int $id): void
    {
        if (!$this->templates->softDelete($id)) {
            throw new NotFoundException('Notification template not found.');
        }
    }

    /**
     * @param array<string, mixed> $sample
     * @return array{subject: string, body: string}
     */
    public function preview(int $id, array $sample = []): array
    {
        $template = $this->get($id);
        $attrs = $template->toArray();

        return [
            'subject' => $this->delivery->render((string) ($attrs['subject_template'] ?? ''), $sample),
            'body' => $this->delivery->render((string) ($attrs['body_template'] ?? ''), $sample),
        ];
    }
}
