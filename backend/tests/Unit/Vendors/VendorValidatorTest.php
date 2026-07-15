<?php

declare(strict_types=1);
namespace SkyFi\Tests\Unit\Vendors;
use PHPUnit\Framework\TestCase;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Vendors\DTOs\ContractData;
use SkyFi\Vendors\DTOs\QuotationData;
use SkyFi\Vendors\DTOs\RatingData;
use SkyFi\Vendors\DTOs\SupplierData;
use SkyFi\Vendors\Validators\ContractValidator;
use SkyFi\Vendors\Validators\QuotationValidator;
use SkyFi\Vendors\Validators\RatingValidator;
use SkyFi\Vendors\Validators\SupplierValidator;
final class VendorValidatorTest extends TestCase
{
 public function testAcceptsValidSupplier():void{$data=SupplierData::fromArray(['supplier_code'=>'sup-001','company_name'=>'Network Hardware Ltd','email'=>'sales@example.com','website'=>'https://example.com','currency'=>'PKR','status'=>'active']);(new SupplierValidator())->validate($data);self::assertSame('SUP-001',$data->supplierCode);}
 public function testRejectsInvalidSupplierCurrency():void{$this->expectException(ValidationException::class);(new SupplierValidator())->validate(SupplierData::fromArray(['supplier_code'=>'SUP-1','company_name'=>'Supplier','currency'=>'RUPEES']));}
 public function testRejectsContractEndBeforeStart():void{$this->expectException(ValidationException::class);(new ContractValidator())->validate(new ContractData('CON-1','2026-07-20','2026-07-01',null,100,'PKR','active',null,null,null));}
 public function testQuotationRequiresLineItem():void{$this->expectException(ValidationException::class);(new QuotationValidator())->validate(new QuotationData('Q-1','RFQ-1','2026-07-15',null,'PKR',0,'received',null,[]));}
 public function testRatingRejectsScoreAboveFive():void{$this->expectException(ValidationException::class);(new RatingValidator())->validate(new RatingData('2026-01-01','2026-07-15',5.1,'PKR',null));}
}
