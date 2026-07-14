<?php

declare(strict_types=1);
namespace SkyFi\Packages\Services;
use SkyFi\Packages\Contracts\{
    PackageRepositoryContract,
    PackageServiceContract,
};
use SkyFi\Packages\Data\{
    BulkPackageActionData,
    CreatePackageData,
    DuplicatePackageData,
    PackageListFilters,
    UpdatePackageData,
};
use SkyFi\Packages\Models\InternetPackage;
use SkyFi\Packages\Validators\PackageValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\{NotFoundException, ValidationException};

final class PackageService implements PackageServiceContract
{
    public function __construct(
        private readonly PackageRepositoryContract $repository,
        private readonly AuditLoggerContract $audit,
    ) {}
    public function list(PackageListFilters $filters): array
    {
        return $this->repository->list($filters);
    }
    public function get(int $id): InternetPackage
    {
        return $this->repository->find($id) ??
            throw new NotFoundException("Package not found.");
    }
    public function create(
        CreatePackageData $data,
        int $userId,
        ?string $ip,
        ?string $ua,
    ): InternetPackage {
        $this->unique($data->values["code"]);
        $p = $this->repository->create($data->values, $userId);
        $this->log($userId, "create", $p->id(), null, $p->toArray(), $ip, $ua);
        return $p;
    }
    public function update(
        int $id,
        UpdatePackageData $data,
        int $userId,
        ?string $ip,
        ?string $ua,
    ): InternetPackage {
        $old = $this->get($id);
        $this->unique($data->values["code"], $id);
        $p = $this->repository->update($id, $data->values, $userId);
        $this->log(
            $userId,
            "update",
            $id,
            $old->toArray(),
            $p->toArray(),
            $ip,
            $ua,
        );
        return $p;
    }
    public function delete(int $id, int $userId, ?string $ip, ?string $ua): void
    {
        $p = $this->get($id);
        if ($this->repository->isInUse($id)) {
            throw new ValidationException([
                [
                    "code" => "package_in_use",
                    "detail" =>
                        "This package is assigned to one or more customers and cannot be deleted.",
                ],
            ]);
        }
        $this->repository->softDelete($id);
        $this->log($userId, "delete", $id, $p->toArray(), null, $ip, $ua);
    }
    public function changeStatus(
        int $id,
        string $status,
        int $userId,
        ?string $ip,
        ?string $ua,
    ): InternetPackage {
        if (
            !in_array(
                $status,
                ["draft", "active", "inactive", "archived"],
                true,
            )
        ) {
            throw new ValidationException([
                [
                    "detail" => "Select a valid package status.",
                    "source" => ["pointer" => "/data/attributes/status"],
                ],
            ]);
        }
        $old = $this->get($id);
        $p = $this->repository->changeStatus($id, $status, $userId);
        $this->log(
            $userId,
            "status_change",
            $id,
            ["status" => $old->status()],
            ["status" => $status],
            $ip,
            $ua,
        );
        return $p;
    }
    public function duplicate(
        int $id,
        DuplicatePackageData $data,
        int $userId,
        ?string $ip,
        ?string $ua,
    ): InternetPackage {
        $source = $this->get($id)->toArray();
        $source["name"] = $data->name ?? $source["name"] . " Copy";
        $source["code"] = $data->code ?? $this->copyCode($source["code"]);
        $source["status"] = "draft";
        $validated = (new PackageValidator())->validate($source);
        $this->unique($validated["code"]);
        $p = $this->repository->create($validated, $userId);
        $this->log(
            $userId,
            "duplicate",
            $p->id(),
            ["source_package_id" => $id],
            $p->toArray(),
            $ip,
            $ua,
        );
        return $p;
    }
    public function bulkStatus(
        BulkPackageActionData $data,
        int $userId,
        ?string $ip,
        ?string $ua,
    ): array {
        $changed = [];
        $failed = [];
        foreach ($data->ids as $id) {
            try {
                $changed[] = $this->changeStatus(
                    $id,
                    (string) $data->status,
                    $userId,
                    $ip,
                    $ua,
                )->id();
            } catch (\Throwable $e) {
                $failed[] = ["id" => $id, "reason" => $e->getMessage()];
            }
        }
        return compact("changed", "failed");
    }
    public function bulkDelete(
        BulkPackageActionData $data,
        int $userId,
        ?string $ip,
        ?string $ua,
    ): array {
        $deleted = [];
        $failed = [];
        foreach ($data->ids as $id) {
            try {
                $this->delete($id, $userId, $ip, $ua);
                $deleted[] = $id;
            } catch (\Throwable $e) {
                $failed[] = ["id" => $id, "reason" => $e->getMessage()];
            }
        }
        return compact("deleted", "failed");
    }
    public function statistics(): array
    {
        return $this->repository->statistics();
    }
    public function activity(int $id): array
    {
        $this->get($id);
        return $this->repository->activity($id);
    }
    private function unique(string $code, ?int $id = null): void
    {
        if ($this->repository->codeExists($code, $id)) {
            throw new ValidationException([
                [
                    "code" => "unique",
                    "detail" => "Package code has already been taken.",
                    "source" => ["pointer" => "/data/attributes/code"],
                ],
            ]);
        }
    }
    private function copyCode(string $code): string
    {
        for ($i = 1; $i <= 99; $i++) {
            $candidate = substr($code, 0, 42) . "-COPY" . ($i === 1 ? "" : $i);
            if (!$this->repository->codeExists($candidate)) {
                return $candidate;
            }
        }
        throw new ValidationException([
            ["detail" => "Unable to generate a unique package code."],
        ]);
    }
    private function log(
        int $u,
        string $a,
        int $id,
        ?array $old,
        ?array $new,
        ?string $ip,
        ?string $ua,
    ): void {
        $this->audit->log($u, $a, "package", $id, $old, $new, $ip, $ua);
    }
}
