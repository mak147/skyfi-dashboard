<?php
declare(strict_types=1);namespace SkyFi\Payments\Models;final class Receipt{public function __construct(public readonly int $id,public readonly int $paymentId,public readonly string $receiptNumber,public readonly string $issuedAt){}}
