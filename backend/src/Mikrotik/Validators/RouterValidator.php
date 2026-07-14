<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Validators;

use SkyFi\Mikrotik\DTOs\ConnectionTestData;
use SkyFi\Mikrotik\DTOs\CreateRouterData;
use SkyFi\Mikrotik\DTOs\UpdateRouterData;
use SkyFi\Shared\Exceptions\ValidationException;

final class RouterValidator
{
    public function validateCreate(CreateRouterData $data): void
    {
        $errors = $this->connectionErrors($data->name, $data->host, $data->apiPort, $data->apiUsername, $data->apiPassword);
        $errors = [...$errors, ...$this->metadataErrors($data->location, $data->site, $data->notes)];

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateUpdate(UpdateRouterData $data): void
    {
        $errors = $this->connectionErrors($data->name, $data->host, $data->apiPort, $data->apiUsername, $data->apiPassword);
        $errors = [...$errors, ...$this->metadataErrors($data->location, $data->site, $data->notes)];

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateConnectionTest(ConnectionTestData $data): void
    {
        $errors = $this->connectionErrors('', $data->host, $data->apiPort, $data->apiUsername, $data->apiPassword, false);
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function connectionErrors(string $name, string $host, int $port, string $username, ?string $password, bool $nameRequired = true): array
    {
        $errors = [];
        if ($nameRequired && ($name === '' || strlen($name) > 150)) {
            $errors[] = $this->error('name', $name === '' ? 'Router name is required.' : 'Router name may not exceed 150 characters.');
        }
        if (!$this->isValidHost($host)) {
            $errors[] = $this->error('host', 'Enter a valid router hostname or IP address.');
        }
        if ($port < 1 || $port > 65535) {
            $errors[] = $this->error('api_port', 'API port must be between 1 and 65535.');
        }
        if ($username === '' || strlen($username) > 128 || preg_match('/[\x00-\x1F\x7F]/', $username) === 1) {
            $errors[] = $this->error('api_username', 'Enter a valid RouterOS API username.');
        }
        if ($password !== null && ($password === '' || strlen($password) > 1024 || preg_match('/[\x00-\x1F\x7F]/', $password) === 1)) {
            $errors[] = $this->error('api_password', 'Enter a valid RouterOS API password.');
        }

        return $errors;
    }

    /** @return array<int, array<string, mixed>> */
    private function metadataErrors(?string $location, ?string $site, ?string $notes): array
    {
        $errors = [];
        if ($location !== null && strlen($location) > 255) {
            $errors[] = $this->error('location', 'Location may not exceed 255 characters.');
        }
        if ($site !== null && strlen($site) > 150) {
            $errors[] = $this->error('site', 'Site may not exceed 150 characters.');
        }
        if ($notes !== null && strlen($notes) > 65535) {
            $errors[] = $this->error('notes', 'Notes are too long.');
        }

        return $errors;
    }

    private function isValidHost(string $host): bool
    {
        if ($host === '' || strlen($host) > 253 || preg_match('/[\x00-\x20\x7F\\\/]/', $host) === 1) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        return preg_match('/^(?=.{1,253}$)(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)*[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', $host) === 1;
    }

    /** @return array<string, mixed> */
    private function error(string $field, string $detail): array
    {
        return ['code' => 'invalid', 'detail' => $detail, 'source' => ['pointer' => '/data/attributes/' . $field]];
    }
}
