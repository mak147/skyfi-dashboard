<?php

declare(strict_types=1);
namespace SkyFi\Payments\Contracts;
use SkyFi\Payments\DTOs\PaymentListFilters;
use SkyFi\Payments\Models\Payment;
interface PaymentRepositoryContract
{
 public function transaction(callable $callback):mixed;
 public function list(PaymentListFilters $filters):array;
 public function find(int $id,bool $lock=false):?Payment;
 public function create(array $data):Payment;
 public function update(int $id,array $data):Payment;
 public function softDelete(int $id):void;
 public function numberExists(string $number):bool;
 public function referenceExists(string $reference,?int $excludeId=null):bool;
 public function customerExists(int $id):bool;
 public function connectionBelongsTo(int $id,int $customerId):bool;
 public function methodAndAccount(int $methodId,int $accountId):?array;
 public function lookups():array;
 public function invoiceForAllocation(int $invoiceId,int $customerId,bool $lock=true):?array;
 public function addAllocation(int $paymentId,array $data):int;
 public function reverseAllocations(int $paymentId,int $userId):array;
 public function refundAllocations(int $paymentId,string $amount,int $userId):array;
 public function recalculateInvoice(int $invoiceId,int $userId,string $description):void;
 public function addReceipt(int $paymentId,string $number,int $userId,array $snapshot):void;
 public function addActivity(int $paymentId,string $action,string $description,int $userId,?array $metadata=null):void;
 public function addCredit(int $customerId,int $paymentId,int $allocationId,string $type,string $amount,int $userId,string $description):void;
 public function addRefund(int $paymentId,string $number,string $amount,string $reason,?string $notes,?string $reference,int $userId):int;
 public function addFinancialEvent(int $paymentId,?int $refundId,string $type,string $amount,array $payload,string $key):void;
 public function statistics():array;
 public function exportRows(PaymentListFilters $filters):array;
}
