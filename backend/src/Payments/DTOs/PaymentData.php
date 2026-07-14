<?php

declare(strict_types=1);
namespace SkyFi\Payments\DTOs;
final class PaymentData
{
 /** @param array<int,array<string,mixed>> $allocations @param array<int,array<string,mixed>> $attachments */
 public function __construct(public readonly int $customerId,public readonly ?int $connectionId,public readonly int $methodId,public readonly int $accountId,public readonly string $amount,public readonly string $tax,public readonly string $discount,public readonly string $adjustment,public readonly string $paymentDate,public readonly ?string $reference,public readonly ?string $notes,public readonly array $allocations,public readonly array $attachments=[],public readonly string $status='pending'){}
 public static function fromArray(array $d,array $clean=[]):self{return new self((int)($clean['customer_id']??$d['customer_id']??0),isset($clean['connection_id'])?$clean['connection_id']:(isset($d['connection_id'])&&(int)$d['connection_id']>0?(int)$d['connection_id']:null),(int)($clean['payment_method_id']??$d['payment_method_id']??0),(int)($clean['payment_account_id']??$d['payment_account_id']??0),(string)($clean['amount']??$d['amount']??'0'),(string)($clean['tax_amount']??$d['tax_amount']??'0'),(string)($clean['discount_amount']??$d['discount_amount']??'0'),(string)($clean['adjustment_amount']??$d['adjustment_amount']??'0'),(string)($clean['payment_date']??$d['payment_date']??date('Y-m-d H:i:s')),isset($d['reference_number'])&&trim((string)$d['reference_number'])!==''?trim((string)$d['reference_number']):null,isset($d['notes'])&&trim((string)$d['notes'])!==''?trim((string)$d['notes']):null,is_array($d['allocations']??null)?$d['allocations']:[],is_array($d['attachments']??null)?$d['attachments']:[],(string)($d['status']??'pending'));}
}
