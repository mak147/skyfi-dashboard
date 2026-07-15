<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Services;

use SkyFi\Monitoring\Contracts\AlertManagementServiceContract;
use SkyFi\Monitoring\Contracts\AlertRepositoryContract;
use SkyFi\Monitoring\Contracts\EventLoggingServiceContract;
use SkyFi\Monitoring\DomainModels\NetworkAlert;
use SkyFi\Monitoring\DTOs\AlertListFilters;
use SkyFi\Monitoring\DTOs\CreateAlertData;
use SkyFi\Monitoring\DTOs\LogMonitoringEventData;
use SkyFi\Monitoring\DTOs\UpdateAlertStatusData;
use SkyFi\Monitoring\Validators\AlertValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;

final class AlertManagementService implements AlertManagementServiceContract
{
    public function __construct(
        private readonly AlertRepositoryContract $alertRepo,
        private readonly EventLoggingServiceContract $eventService,
        private readonly AlertValidator $validator,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    /** @return array{items: array<int, NetworkAlert>, total: int, page: int, per_page: int} */
    public function listAlerts(AlertListFilters $filters): array
    {
        return $this->alertRepo->listAlerts($filters);
    }

    public function getAlert(int $id): array
    {
        $alert = $this->alertRepo->findAlert($id);
        if ($alert === null) {
            throw new NotFoundException('Alert not found.');
        }

        $history = $this->alertRepo->getHistoryForAlert($id);

        return [
            'alert' => $alert->toArray(),
            'history' => array_map(static fn ($h) => $h->toArray(), $history),
        ];
    }

    public function createAlert(CreateAlertData $data): NetworkAlert
    {
        $this->validator->validateCreate($data);
        $alert = $this->alertRepo->createAlert($data);

        // Log monitoring event
        $this->eventService->logMonitoringEvent(new LogMonitoringEventData(
            eventType: 'alert_triggered',
            severity: $data->severity,
            sourceType: $data->deviceType,
            sourceId: $data->deviceId,
            message: "Alert triggered: {$data->title}",
            metadata: ['alert_id' => $alert->id, 'metric_value' => $data->metricValue, 'threshold_value' => $data->thresholdValue],
        ));

        \SkyFi\Shared\Events\EventDispatcher::dispatch('monitoring.alert.triggered', $alert->toArray());

        return $alert;
    }

    public function acknowledgeAlert(int $id, ?int $actorId = null, ?string $notes = null, ?string $ip = null, ?string $userAgent = null): NetworkAlert
    {
        return $this->updateAlertStatus($id, new UpdateAlertStatusData('acknowledged', $notes), $actorId, $ip, $userAgent);
    }

    public function resolveAlert(int $id, ?int $actorId = null, ?string $notes = null, ?string $ip = null, ?string $userAgent = null): NetworkAlert
    {
        return $this->updateAlertStatus($id, new UpdateAlertStatusData('resolved', $notes), $actorId, $ip, $userAgent);
    }

    public function dismissAlert(int $id, ?int $actorId = null, ?string $notes = null, ?string $ip = null, ?string $userAgent = null): NetworkAlert
    {
        return $this->updateAlertStatus($id, new UpdateAlertStatusData('dismissed', $notes), $actorId, $ip, $userAgent);
    }

    public function updateAlertStatus(int $id, UpdateAlertStatusData $data, ?int $actorId = null, ?string $ip = null, ?string $userAgent = null): NetworkAlert
    {
        $this->validator->validateStatusUpdate($data);
        $alert = $this->alertRepo->updateAlertStatus($id, $data->status, $actorId, $data->notes);

        if ($actorId !== null) {
            $this->auditLogger->log(
                $actorId,
                'update_alert_status',
                'monitoring_alert',
                $id,
                ['status' => $data->status, 'notes' => $data->notes],
                null,
                $ip,
                $userAgent
            );
        }

        $this->eventService->logMonitoringEvent(new LogMonitoringEventData(
            eventType: 'device_status_change',
            severity: 'info',
            sourceType: $alert->deviceType,
            sourceId: $alert->deviceId,
            message: "Alert #{$id} status updated to {$data->status}",
            metadata: ['alert_id' => $id, 'notes' => $data->notes],
        ));

        return $alert;
    }
}
