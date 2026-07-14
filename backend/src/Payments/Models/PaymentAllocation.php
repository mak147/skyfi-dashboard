<?php
declare(strict_types=1);namespace SkyFi\Payments\Models;final class PaymentAllocation{public function __construct(public readonly int $id,public readonly int $paymentId,public readonly ?int $invoiceId,public readonly string $type,public readonly string $amount){}}
