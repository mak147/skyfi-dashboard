<?php

declare(strict_types=1);
namespace SkyFi\Tests\Unit\Payments;
use PHPUnit\Framework\TestCase;use SkyFi\Payments\Validators\PaymentValidator;use SkyFi\Shared\Exceptions\ValidationException;
final class PaymentValidatorTest extends TestCase
{
 public function testItNormalizesAReceivedPayment():void{$data=(new PaymentValidator())->validate($this->valid(),true);self::assertSame('1500.00',$data['amount']);self::assertSame('completed',$data['status']);}
 public function testItRejectsNonPositiveAmounts():void{$p=$this->valid();$p['amount']=0;$this->expectException(ValidationException::class);(new PaymentValidator())->validate($p);}
 public function testItRejectsMissingRelationships():void{$p=$this->valid();$p['customer_id']=0;$this->expectException(ValidationException::class);(new PaymentValidator())->validate($p);}
 public function testPositiveAmountNormalizesDecimals():void{self::assertSame('12.50',(new PaymentValidator())->positiveAmount('12.5'));}
 private function valid():array{return['customer_id'=>1,'payment_method_id'=>1,'payment_account_id'=>1,'amount'=>'1500','tax_amount'=>0,'discount_amount'=>0,'adjustment_amount'=>0,'payment_date'=>'2026-07-14 10:00:00','status'=>'pending'];}
}
