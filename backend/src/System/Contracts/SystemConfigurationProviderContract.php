<?php declare(strict_types=1);
namespace SkyFi\System\Contracts;
interface SystemConfigurationProviderContract { public function all(): array; public function company(): array; public function system(): array; public function branding(): array; public function localization(): array; public function notifications(): array; public function currencyCode(): string; public function timezone(): string; public function dateFormat(): string; public function applicationName(): string; public function fileUploadLimits(): array; }
