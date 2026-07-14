<?php

declare(strict_types=1);
namespace SkyFi\Packages\Data;
use SkyFi\Packages\Validators\PackageValidator;
final class CreatePackageData { private function __construct(public readonly array $values) {} public static function fromArray(array $input): self { return new self((new PackageValidator())->validate($input)); } }
