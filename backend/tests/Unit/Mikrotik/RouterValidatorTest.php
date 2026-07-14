<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Mikrotik;

use PHPUnit\Framework\TestCase;
use SkyFi\Mikrotik\DTOs\ConnectionTestData;
use SkyFi\Mikrotik\DTOs\CreateRouterData;
use SkyFi\Mikrotik\DTOs\UpdateRouterData;
use SkyFi\Mikrotik\Validators\RouterValidator;
use SkyFi\Shared\Exceptions\ValidationException;

final class RouterValidatorTest extends TestCase
{
    public function testItAcceptsTlsRouterCredentials(): void
    {
        $data = CreateRouterData::fromArray([
            'name' => 'Lahore Core',
            'host' => '10.10.0.1',
            'api_port' => 8729,
            'api_username' => 'skyfi-api',
            'api_password' => 'strong-api-password',
            'tag_ids' => [1, 2, 2],
            'is_enabled' => true,
        ]);

        (new RouterValidator())->validateCreate($data);

        self::assertSame([1, 2], $data->tagIds);
    }

    public function testItRejectsAnInvalidHost(): void
    {
        $data = CreateRouterData::fromArray([
            'name' => 'Invalid',
            'host' => 'router invalid/host',
            'api_username' => 'skyfi-api',
            'api_password' => 'password',
        ]);

        $this->expectException(ValidationException::class);
        (new RouterValidator())->validateCreate($data);
    }

    public function testItPermitsBlankPasswordOnRouterUpdate(): void
    {
        $data = UpdateRouterData::fromArray([
            'name' => 'Lahore Core',
            'host' => 'router.lahore.internal',
            'api_port' => 8729,
            'api_username' => 'skyfi-api',
            'api_password' => '',
        ]);

        (new RouterValidator())->validateUpdate($data);

        self::assertNull($data->apiPassword);
    }

    public function testItRequiresPasswordForAnEphemeralConnectionTest(): void
    {
        $data = ConnectionTestData::fromArray([
            'host' => '10.10.0.1',
            'api_port' => 8729,
            'api_username' => 'skyfi-api',
            'api_password' => '',
        ]);

        $this->expectException(ValidationException::class);
        (new RouterValidator())->validateConnectionTest($data);
    }
}
