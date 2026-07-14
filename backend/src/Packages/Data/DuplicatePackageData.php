<?php

declare(strict_types=1);
namespace SkyFi\Packages\Data;
final class DuplicatePackageData
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $code,
    ) {}
    public static function fromArray(array $input): self
    {
        $name =
            is_string($input["name"] ?? null) && trim($input["name"]) !== ""
                ? trim($input["name"])
                : null;
        $code =
            is_string($input["code"] ?? null) && trim($input["code"]) !== ""
                ? strtoupper(trim($input["code"]))
                : null;
        return new self($name, $code);
    }
}
