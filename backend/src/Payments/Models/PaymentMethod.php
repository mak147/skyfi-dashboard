<?php
declare(strict_types=1);namespace SkyFi\Payments\Models;final class PaymentMethod{public function __construct(public readonly int $id,public readonly string $code,public readonly string $name,public readonly bool $active,public readonly bool $future){}}
