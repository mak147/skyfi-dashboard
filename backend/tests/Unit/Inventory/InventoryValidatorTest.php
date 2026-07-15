<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Inventory;

use PHPUnit\Framework\TestCase;
use SkyFi\Inventory\DTOs\AssetAssignmentData;
use SkyFi\Inventory\DTOs\ProductData;
use SkyFi\Inventory\DTOs\StockOperationData;
use SkyFi\Inventory\DTOs\TransferData;
use SkyFi\Inventory\Validators\AssetValidator;
use SkyFi\Inventory\Validators\ProductValidator;
use SkyFi\Inventory\Validators\StockValidator;
use SkyFi\Inventory\Validators\TransferValidator;
use SkyFi\Shared\Exceptions\ValidationException;

final class InventoryValidatorTest extends TestCase
{
    public function testAcceptsAValidSerializedProduct(): void
    {
        $data = ProductData::fromArray([
            'category_id' => 1,
            'unit_id' => 1,
            'sku' => 'cpe-001',
            'name' => 'Customer CPE',
            'tracking_mode' => 'serialized',
            'standard_cost' => 12500,
            'minimum_stock' => 2,
            'reorder_level' => 5,
            'status' => 'active',
        ]);
        (new ProductValidator())->validate($data);
        self::assertSame('CPE-001', $data->sku);
    }

    public function testRejectsNegativeProductCost(): void
    {
        $data = new ProductData(1, null, 1, 'CPE-001', 'CPE', null, null, null, 'serialized', '-1.0000', '0.0000', '0.0000', 'active', []);
        $this->expectException(ValidationException::class);
        (new ProductValidator())->validate($data);
    }

    public function testAssignmentRequiresExactlyOneMatchingTarget(): void
    {
        $this->expectException(ValidationException::class);
        (new AssetValidator())->validateAssignment(new AssetAssignmentData('customer', null, 3, 5, null, null, null));
    }

    public function testAdjustmentRequiresAReason(): void
    {
        $operation = StockOperationData::fromArray('adjustment_out', [
            'lines' => [['product_id' => 1, 'source_location_id' => 2, 'quantity' => 1]],
        ]);
        $this->expectException(ValidationException::class);
        (new StockValidator())->validate($operation);
    }

    public function testTransferWarehousesMustDiffer(): void
    {
        $data = new TransferData(1, 1, [['product_id' => 1, 'source_location_id' => 1, 'destination_location_id' => 2, 'quantity_requested' => 1]], null, null);
        $this->expectException(ValidationException::class);
        (new TransferValidator())->validate($data);
    }
}
