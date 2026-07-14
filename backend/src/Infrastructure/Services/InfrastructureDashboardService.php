<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Services;

use PDO;
use SkyFi\Infrastructure\Contracts\InfrastructureDashboardContract;
use SkyFi\Infrastructure\Data\InfrastructureDashboardPayload;

final class InfrastructureDashboardService implements InfrastructureDashboardContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getSummary(): InfrastructureDashboardPayload
    {
        // POP Sites
        $totalPopSites = (int) $this->pdo->query("SELECT COUNT(*) FROM pop_sites WHERE deleted_at IS NULL")->fetchColumn();
        $activePopSites = (int) $this->pdo->query("SELECT COUNT(*) FROM pop_sites WHERE deleted_at IS NULL AND status = 'active'")->fetchColumn();

        // Towers
        $totalTowers = (int) $this->pdo->query("SELECT COUNT(*) FROM towers WHERE deleted_at IS NULL")->fetchColumn();
        $activeTowers = (int) $this->pdo->query("SELECT COUNT(*) FROM towers WHERE deleted_at IS NULL AND status = 'active'")->fetchColumn();

        // Sectors
        $totalSectors = (int) $this->pdo->query("SELECT COUNT(*) FROM sectors WHERE deleted_at IS NULL")->fetchColumn();
        $activeSectors = (int) $this->pdo->query("SELECT COUNT(*) FROM sectors WHERE deleted_at IS NULL AND status = 'active'")->fetchColumn();

        // Devices
        $totalDevices = (int) $this->pdo->query("SELECT COUNT(*) FROM network_devices WHERE deleted_at IS NULL")->fetchColumn();
        $activeDevices = (int) $this->pdo->query("SELECT COUNT(*) FROM network_devices WHERE deleted_at IS NULL AND status = 'deployed'")->fetchColumn();
        $offlineDevices = (int) $this->pdo->query("SELECT COUNT(*) FROM network_devices WHERE deleted_at IS NULL AND status = 'offline'")->fetchColumn();
        $maintenanceDevices = (int) $this->pdo->query("SELECT COUNT(*) FROM network_devices WHERE deleted_at IS NULL AND status = 'maintenance'")->fetchColumn();

        // Capacity Summary - active sectors with capacity info
        $stmt = $this->pdo->query("
            SELECT s.id, s.name, s.capacity_mbps,
                   COUNT(c.id) AS connected_count
            FROM sectors s
            LEFT JOIN connections c ON c.sector_id = s.id AND c.deleted_at IS NULL AND c.status = 'active'
            WHERE s.deleted_at IS NULL AND s.status = 'active'
            GROUP BY s.id
            ORDER BY s.name
        ");
        $capacitySummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Status Breakdown
        $popSiteStatuses = $this->pdo->query("
            SELECT status, COUNT(*) as count
            FROM pop_sites
            WHERE deleted_at IS NULL
            GROUP BY status
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        $towerStatuses = $this->pdo->query("
            SELECT status, COUNT(*) as count
            FROM towers
            WHERE deleted_at IS NULL
            GROUP BY status
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        $sectorStatuses = $this->pdo->query("
            SELECT status, COUNT(*) as count
            FROM sectors
            WHERE deleted_at IS NULL
            GROUP BY status
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        $deviceStatuses = $this->pdo->query("
            SELECT status, COUNT(*) as count
            FROM network_devices
            WHERE deleted_at IS NULL
            GROUP BY status
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        return new InfrastructureDashboardPayload(
            totalPopSites: $totalPopSites,
            activePopSites: $activePopSites,
            totalTowers: $totalTowers,
            activeTowers: $activeTowers,
            totalSectors: $totalSectors,
            activeSectors: $activeSectors,
            totalDevices: $totalDevices,
            activeDevices: $activeDevices,
            offlineDevices: $offlineDevices,
            maintenanceDevices: $maintenanceDevices,
            capacitySummary: $capacitySummary,
            statusBreakdown: [
                'pop_sites' => $popSiteStatuses,
                'towers' => $towerStatuses,
                'sectors' => $sectorStatuses,
                'devices' => $deviceStatuses,
            ],
        );
    }
}