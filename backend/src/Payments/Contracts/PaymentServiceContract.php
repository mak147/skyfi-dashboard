<?php

declare(strict_types=1);
namespace SkyFi\Payments\Contracts;
use SkyFi\Payments\DTOs\PaymentData;use SkyFi\Payments\DTOs\PaymentListFilters;use SkyFi\Payments\Models\Payment;
interface PaymentServiceContract
{
 public function list(PaymentListFilters $filters):array; public function get(int $id):Payment;
 public function create(PaymentData $data,int $user,?string $ip,?string $ua):Payment;
 public function receive(PaymentData $data,int $user,?string $ip,?string $ua):Payment;
 public function update(int $id,PaymentData $data,int $user,?string $ip,?string $ua):Payment;
 public function delete(int $id,int $user,?string $ip,?string $ua):void;
 public function allocate(int $id,array $allocations,int $user,?string $ip,?string $ua):Payment;
 public function reverse(int $id,string $reason,int $user,?string $ip,?string $ua):Payment;
 public function refund(int $id,array $data,int $user,?string $ip,?string $ua):Payment;
 public function bulk(array $items,int $user,?string $ip,?string $ua):array;
 public function lookups():array;public function statistics():array;public function export(PaymentListFilters $filters):array;
}
