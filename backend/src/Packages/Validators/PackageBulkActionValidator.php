<?php

declare(strict_types=1);
namespace SkyFi\Packages\Validators;
use SkyFi\Packages\Data\BulkPackageActionData;
final class PackageBulkActionValidator
{
    public function validate(
        array $input,
        bool $status = false,
    ): BulkPackageActionData {
        return BulkPackageActionData::fromArray($input, $status);
    }
}
