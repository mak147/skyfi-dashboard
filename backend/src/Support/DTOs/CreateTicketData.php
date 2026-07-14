<?php

declare(strict_types=1);
namespace SkyFi\Support\DTOs;
final class CreateTicketData
{
    public function __construct(
        public readonly int $customerId,
        public readonly ?int $connectionId,
        public readonly ?int $packageId,
        public readonly ?int $pppoeAccountId,
        public readonly ?int $hotspotUserId,
        public readonly ?int $routerId,
        public readonly ?int $networkDeviceId,
        public readonly ?int $monitoringAlertId,
        public readonly int $categoryId,
        public readonly string $priority,
        public readonly string $source,
        public readonly string $subject,
        public readonly string $description,
    ) {}
    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = static fn(string $key): ?int => isset($data[$key]) &&
        $data[$key] !== ""
            ? (int) $data[$key]
            : null;
        return new self(
            (int) ($data["customer_id"] ?? 0),
            $id("connection_id"),
            $id("package_id"),
            $id("pppoe_account_id"),
            $id("hotspot_user_id"),
            $id("router_id"),
            $id("network_device_id"),
            $id("monitoring_alert_id"),
            (int) ($data["category_id"] ?? 0),
            (string) ($data["priority"] ?? "normal"),
            (string) ($data["source"] ?? "staff"),
            trim((string) ($data["subject"] ?? "")),
            trim((string) ($data["description"] ?? "")),
        );
    }
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            "customer_id" => $this->customerId,
            "connection_id" => $this->connectionId,
            "package_id" => $this->packageId,
            "pppoe_account_id" => $this->pppoeAccountId,
            "hotspot_user_id" => $this->hotspotUserId,
            "router_id" => $this->routerId,
            "network_device_id" => $this->networkDeviceId,
            "monitoring_alert_id" => $this->monitoringAlertId,
            "category_id" => $this->categoryId,
            "priority" => $this->priority,
            "source" => $this->source,
            "subject" => $this->subject,
            "description" => $this->description,
        ];
    }
}
