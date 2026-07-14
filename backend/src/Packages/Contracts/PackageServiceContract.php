<?php

declare(strict_types=1);
namespace SkyFi\Packages\Contracts;
use SkyFi\Packages\Data\{BulkPackageActionData,CreatePackageData,DuplicatePackageData,PackageListFilters,UpdatePackageData};
use SkyFi\Packages\Models\InternetPackage;
interface PackageServiceContract {
 public function list(PackageListFilters $filters): array;
 public function get(int $id): InternetPackage;
 public function create(CreatePackageData $data,int $userId,?string $ip,?string $ua): InternetPackage;
 public function update(int $id,UpdatePackageData $data,int $userId,?string $ip,?string $ua): InternetPackage;
 public function delete(int $id,int $userId,?string $ip,?string $ua): void;
 public function changeStatus(int $id,string $status,int $userId,?string $ip,?string $ua): InternetPackage;
 public function duplicate(int $id,DuplicatePackageData $data,int $userId,?string $ip,?string $ua): InternetPackage;
 public function bulkStatus(BulkPackageActionData $data,int $userId,?string $ip,?string $ua): array;
 public function bulkDelete(BulkPackageActionData $data,int $userId,?string $ip,?string $ua): array;
 public function statistics(): array;
 public function activity(int $id): array;
}
