<?php

declare(strict_types=1);
namespace SkyFi\Support\DTOs;
final class UpdateTicketData
{
    /** @param array<string, mixed> $values */ public function __construct(
        public readonly array $values,
    ) {}
    /** @param array<string, mixed> $data */ public static function fromArray(
        array $data,
    ): self {
        $allowed = [
            "customer_id",
            "connection_id",
            "package_id",
            "pppoe_account_id",
            "hotspot_user_id",
            "router_id",
            "network_device_id",
            "monitoring_alert_id",
            "category_id",
            "priority",
            "subject",
            "description",
            "resolution",
            "root_cause",
        ];
        return new self(array_intersect_key($data, array_flip($allowed)));
    }
}
