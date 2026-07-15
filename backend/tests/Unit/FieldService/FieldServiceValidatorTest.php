<?php
declare(strict_types=1);
namespace SkyFi\Tests\Unit\FieldService;
use PHPUnit\Framework\TestCase;
use SkyFi\FieldService\Validators\FieldServiceValidator;
use SkyFi\Shared\Exceptions\ValidationException;
final class FieldServiceValidatorTest extends TestCase
{
    public function testAllowsDocumentedTransition():void { (new FieldServiceValidator())->transition('scheduled','in_progress'); self::assertTrue(true); }
    public function testRejectsCompletionFromPending():void { $this->expectException(ValidationException::class); (new FieldServiceValidator())->transition('pending','completed'); }
}
