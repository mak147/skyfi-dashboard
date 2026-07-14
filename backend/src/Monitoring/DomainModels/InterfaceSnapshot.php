<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DomainModels;

final class InterfaceSnapshot
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $routerId,
        public readonly string $interfaceName,
        public readonly ?string $interfaceType,
        public readonly bool $running,
        public readonly bool $disabled,
        public readonly ?int $mtu,
        public readonly int $rxBytes,
        public readonly int $txBytes,
        public readonly int $rxBps,
        public readonly int $txBps,
        public readonly string $linkStatus,
        public readonly string $checkedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'router_id' => $this->routerId,
            'interface_name' => $this->interfaceName,
            'interface_type' => $this->interfaceType,
            'running' => $this->running,
            'disabled' => $this->disabled,
            'mtu' => $this->mtu,
            'rx_bytes' => $this->rxBytes,
            'tx_bytes' => $this->txBytes,
            'rx_bps' => $this->rxBps,
            'tx_bps' => $this->txBps,
            'link_status' => $this->linkStatus,
            'checked_at' => $this->checkedAt,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            routerId: (int) $row['router_id'],
            interfaceName: (string) $row['interface_name'],
            interfaceType: isset($row['interface_type']) ? (string) $row['interface_type'] : null,
            running: (bool) ($row['running'] ?? false),
            disabled: (bool) ($row['disabled'] ?? false),
            mtu: isset($row['mtu']) && $row['mtu'] !== null ? (int) $row['mtu'] : null,
            rxBytes: (int) ($row['rx_bytes'] ?? 0),
            txBytes: (int) ($row['tx_bytes'] ?? 0),
            rxBps: (int) ($row['rx_bps'] ?? 0),
            txBps: (int) ($row['tx_bps'] ?? 0),
            linkStatus: (string) ($row['link_status'] ?? 'down'),
            checkedAt: (string) $row['checked_at'],
        );
    }
}
