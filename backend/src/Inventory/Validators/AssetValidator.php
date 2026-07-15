<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Validators;

use SkyFi\Inventory\DTOs\AssetAssignmentData;
use SkyFi\Inventory\DTOs\AssetData;
use SkyFi\Shared\Exceptions\ValidationException;

final class AssetValidator
{
    private const STATUSES = ['in_stock', 'reserved', 'in_transit', 'assigned', 'deployed', 'under_repair', 'returned', 'damaged', 'lost', 'scrapped', 'retired'];

    public function validate(AssetData $data): void
    {
        $errors = [];
        if ($data->productId < 1) {
            $errors[] = $this->error('product_id', 'A serialized product is required.');
        }
        if ($data->assetTag === '' || strlen($data->assetTag) > 80 || !preg_match('/^[A-Z0-9._-]+$/', $data->assetTag)) {
            $errors[] = $this->error('asset_tag', 'Asset tag is required and has an invalid format.');
        }
        if ($data->serialNumber === '' || mb_strlen($data->serialNumber) > 150) {
            $errors[] = $this->error('serial_number', 'Serial number is required and must not exceed 150 characters.');
        }
        if ($data->macAddress !== null && !filter_var($data->macAddress, FILTER_VALIDATE_MAC)) {
            $errors[] = $this->error('mac_address', 'Enter a valid MAC address.');
        }
        if (!in_array($data->status, self::STATUSES, true)) {
            $errors[] = $this->error('status', 'Asset status is invalid.');
        }
        if ((float) $data->acquisitionCost < 0) {
            $errors[] = $this->error('acquisition_cost', 'Acquisition cost cannot be negative.');
        }
        if ($data->warrantyStartsAt !== null && !$this->date($data->warrantyStartsAt)) {
            $errors[] = $this->error('warranty_starts_at', 'Warranty start date is invalid.');
        }
        if ($data->warrantyExpiresAt !== null && !$this->date($data->warrantyExpiresAt)) {
            $errors[] = $this->error('warranty_expires_at', 'Warranty expiry date is invalid.');
        }
        if ($data->warrantyStartsAt !== null && $data->warrantyExpiresAt !== null && $data->warrantyExpiresAt < $data->warrantyStartsAt) {
            $errors[] = $this->error('warranty_expires_at', 'Warranty expiry cannot precede its start date.');
        }
        if ($data->initialAssignment !== null) {
            try {
                $this->validateAssignment($data->initialAssignment);
            } catch (ValidationException $exception) {
                $errors = [...$errors, ...$exception->details()];
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateAssignment(AssetAssignmentData $data): void
    {
        $targets = [
            'warehouse' => $data->warehouseLocationId,
            'customer' => $data->customerId,
            'tower' => $data->towerId,
            'pop_site' => $data->popSiteId,
            'technician' => $data->technicianId,
        ];
        $selected = array_filter($targets, static fn(?int $id): bool => $id !== null && $id > 0);
        if (!array_key_exists($data->assignmentType, $targets) || count($selected) !== 1 || ($targets[$data->assignmentType] ?? null) === null) {
            throw new ValidationException([$this->error('assignment', 'Select exactly one destination matching the assignment type.')]);
        }
    }

    public function validateStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new ValidationException([$this->error('status', 'Asset status is invalid.')]);
        }
    }

    private function date(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    /** @return array<string, mixed> */
    private function error(string $field, string $detail): array
    {
        return ['code' => 'validation_error', 'detail' => $detail, 'source' => ['pointer' => '/data/attributes/' . $field]];
    }
}
