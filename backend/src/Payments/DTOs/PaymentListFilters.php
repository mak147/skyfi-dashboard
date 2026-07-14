<?php

declare(strict_types=1);
namespace SkyFi\Payments\DTOs;
final class PaymentListFilters
{
 public function __construct(public readonly int $page=1,public readonly int $perPage=15,public readonly ?string $status=null,public readonly ?string $method=null,public readonly ?int $accountId=null,public readonly ?int $customerId=null,public readonly ?int $invoiceId=null,public readonly ?int $connectionId=null,public readonly ?int $collectedBy=null,public readonly ?string $dateFrom=null,public readonly ?string $dateTo=null,public readonly ?string $minAmount=null,public readonly ?string $maxAmount=null,public readonly ?string $search=null,public readonly string $sort='-payment_date'){}
 public static function fromQuery(array $q):self { $f=is_array($q['filter']??null)?$q['filter']:[]; return new self(max(1,(int)($q['page']['number']??1)),min(100,max(1,(int)($q['page']['size']??15))),self::s($f,'status'),self::s($f,'payment_method'),self::i($f,'account_id'),self::i($f,'customer_id'),self::i($f,'invoice_id'),self::i($f,'connection_id'),self::i($f,'collected_by'),self::s($f,'date_from'),self::s($f,'date_to'),self::s($f,'min_amount'),self::s($f,'max_amount'),self::s($f,'search'),is_string($q['sort']??null)?$q['sort']:'-payment_date'); }
 private static function s(array $a,string $k):?string{return isset($a[$k])&&is_string($a[$k])&&trim($a[$k])!==''?trim($a[$k]):null;}
 private static function i(array $a,string $k):?int{return isset($a[$k])&&(int)$a[$k]>0?(int)$a[$k]:null;}
}
