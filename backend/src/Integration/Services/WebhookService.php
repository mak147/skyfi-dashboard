<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

use SkyFi\Integration\Contracts\WebhookRepositoryContract;
use SkyFi\Integration\DomainModels\Webhook;
use SkyFi\Integration\DTOs\CreateWebhookData;
use SkyFi\Integration\DTOs\UpdateWebhookData;
use SkyFi\Integration\DTOs\WebhookListFilters;
use SkyFi\Shared\Exceptions\NotFoundException;

final class WebhookService
{
    public function __construct(
        private readonly WebhookRepositoryContract $webhooks,
    ) {}

    /** @return array{items: list<Webhook>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(WebhookListFilters $filters): array
    {
        return $this->webhooks->list($filters);
    }

    public function get(int $id): Webhook
    {
        return $this->webhooks->find($id)
            ?? throw new NotFoundException('Webhook not found.');
    }

    public function create(int $userId, CreateWebhookData $data): Webhook
    {
        $secret = $this->generateSecret();
        $inboundSecret = $data->isInbound ? $this->generateSecret() : null;

        return $this->webhooks->create([
            'client_application_id' => $data->clientApplicationId,
            'name' => $data->name,
            'url' => $data->url,
            'secret' => $secret,
            'events' => $data->events,
            'is_active' => $data->isActive,
            'is_inbound' => $data->isInbound,
            'inbound_secret' => $inboundSecret,
            'retry_policy' => $data->retryPolicy,
            'filter_rules' => $data->filterRules,
            'content_type' => $data->contentType,
            'created_by' => $userId,
        ]);
    }

    public function update(int $id, int $userId, UpdateWebhookData $data): Webhook
    {
        $this->get($id);
        $updateData = [];
        if ($data->name !== null) {
            $updateData['name'] = $data->name;
        }
        if ($data->url !== null) {
            $updateData['url'] = $data->url;
        }
        if ($data->events !== null) {
            $updateData['events'] = $data->events;
        }
        if ($data->isActive !== null) {
            $updateData['is_active'] = $data->isActive;
        }
        if ($data->retryPolicy !== null) {
            $updateData['retry_policy'] = $data->retryPolicy;
        }
        if ($data->filterRules !== null) {
            $updateData['filter_rules'] = $data->filterRules;
        }
        if ($data->contentType !== null) {
            $updateData['content_type'] = $data->contentType;
        }

        return $this->webhooks->update($id, $updateData)
            ?? throw new NotFoundException('Webhook not found after update.');
    }

    public function delete(int $id): void
    {
        if (!$this->webhooks->delete($id)) {
            throw new NotFoundException('Webhook not found.');
        }
    }

    /** @return array{webhook: Webhook, new_secret: string} */
    public function rotateSecret(int $id): array
    {
        $this->get($id);
        $newSecret = $this->generateSecret();
        $webhook = $this->webhooks->update($id, ['secret' => $newSecret])
            ?? throw new NotFoundException('Webhook not found after secret rotation.');

        return [
            'webhook' => $webhook,
            'new_secret' => $newSecret,
        ];
    }

    /** @param array<string, mixed> $payload @return array{delivery_id: int, status: string} */
    public function test(int $id, array $payload = []): array
    {
        $webhook = $this->get($id);
        $testPayload = $payload !== [] ? $payload : [
            'event' => 'test.ping',
            'timestamp' => gmdate('c'),
            'data' => ['message' => 'Test webhook from SkyFi Integration Platform'],
        ];

        $signature = (new WebhookSignatureService())->sign($testPayload, $webhook->toArrayWithSecrets()['secret'] ?? '');

        return [
            'delivery_id' => 0,
            'status' => 'test_dispatched',
            'payload' => $testPayload,
            'signature' => $signature,
        ];
    }

    private function generateSecret(): string
    {
        return 'whsec_' . bin2hex(random_bytes(24));
    }
}
