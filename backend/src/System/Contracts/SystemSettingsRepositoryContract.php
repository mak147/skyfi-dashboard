<?php declare(strict_types=1);
namespace SkyFi\System\Contracts;
interface SystemSettingsRepositoryContract { public function first(): array; public function update(array $data): array; public function dashboard(): array; }
