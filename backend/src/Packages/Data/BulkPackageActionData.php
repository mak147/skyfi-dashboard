<?php

declare(strict_types=1);
namespace SkyFi\Packages\Data;
use SkyFi\Shared\Exceptions\ValidationException;
final class BulkPackageActionData
{
    public function __construct(
        public readonly array $ids,
        public readonly ?string $status,
    ) {}
    public static function fromArray(
        array $input,
        bool $needsStatus = false,
    ): self {
        $ids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        "intval",
                        is_array($input["ids"] ?? null) ? $input["ids"] : [],
                    ),
                    fn($id) => $id > 0,
                ),
            ),
        );
        $status = is_string($input["status"] ?? null) ? $input["status"] : null;
        $errors = [];
        if ($ids === [] || count($ids) > 100) {
            $errors[] = [
                "detail" => "Provide between 1 and 100 valid package IDs.",
                "source" => ["pointer" => "/data/attributes/ids"],
            ];
        }
        if (
            $needsStatus &&
            !in_array(
                $status,
                ["draft", "active", "inactive", "archived"],
                true,
            )
        ) {
            $errors[] = [
                "detail" => "Select a valid package status.",
                "source" => ["pointer" => "/data/attributes/status"],
            ];
        }
        if ($errors) {
            throw new ValidationException($errors);
        }
        return new self($ids, $status);
    }
}
