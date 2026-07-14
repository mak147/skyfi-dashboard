<?php

declare(strict_types=1);

namespace SkyFi\Connections\Data;

final class CreateConnectionData
{
    public function __construct(
        public readonly string $name,
        public readonly int $customerId,
        public readonly int $packageId,
        public readonly string $type,
        public readonly ?string $pppoeUsername = null,
        public readonly ?string $pppoePassword = null,
        public readonly ?string $staticIp = null,
        public readonly ?string $gateway = null,
        public readonly ?string $dnsServers = null,
        public readonly ?string $macAddress = null,
        public readonly ?int $vlanId = null,
        public readonly ?string $radiusProfile = null,
        public readonly ?string $queueName = null,
        public readonly ?string $popSite = null,
        public readonly ?string $tower = null,
        public readonly ?string $sector = null,
        public readonly ?string $accessPoint = null,
        public readonly ?string $assignedRouter = null,
        public readonly ?string $installationDate = null,
        public readonly ?string $installationTeam = null,
        public readonly ?int $technicianId = null,
        public readonly float $installationCost = 0.0,
        public readonly ?string $installationNotes = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            customerId: (int) ($data['customer_id'] ?? 0),
            packageId: (int) ($data['package_id'] ?? 0),
            type: (string) ($data['type'] ?? 'pppoe'),
            pppoeUsername: $data['pppoe_username'] ?? null,
            pppoePassword: $data['pppoe_password'] ?? null,
            staticIp: $data['static_ip'] ?? null,
            gateway: $data['gateway'] ?? null,
            dnsServers: $data['dns_servers'] ?? null,
            macAddress: $data['mac_address'] ?? null,
            vlanId: isset($data['vlan_id']) ? (int) $data['vlan_id'] : null,
            radiusProfile: $data['radius_profile'] ?? null,
            queueName: $data['queue_name'] ?? null,
            popSite: $data['pop_site'] ?? null,
            tower: $data['tower'] ?? null,
            sector: $data['sector'] ?? null,
            accessPoint: $data['access_point'] ?? null,
            assigned_router: $data['assigned_router'] ?? null,
            installationDate: $data['installation_date'] ?? null,
            installationTeam: $data['installation_team'] ?? null,
            technicianId: isset($data['technician_id']) ? (int) $data['technician_id'] : null,
            installationCost: (float) ($data['installation_cost'] ?? 0.0),
            installationNotes: $data['installation_notes'] ?? null,
        );
    }
}
