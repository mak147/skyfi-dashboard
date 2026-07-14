<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Packages;

use PHPUnit\Framework\TestCase;
use SkyFi\Packages\Validators\PackageValidator;
use SkyFi\Shared\Exceptions\ValidationException;

final class PackageValidatorTest extends TestCase
{
    public function testItNormalizesACompletePackage(): void
    {
        $data = (new PackageValidator())->validate($this->validPayload());

        self::assertSame("HOME-20", $data["code"]);
        self::assertSame("residential", $data["category"]);
        self::assertSame(20000, $data["bandwidth"]["download_kbps"]);
        self::assertNull($data["bandwidth"]["data_limit_bytes"]);
        self::assertSame("2500.00", $data["pricing"]["monthly"]);
    }

    public function testItRequiresADataLimitForLimitedPackages(): void
    {
        $payload = $this->validPayload();
        $payload["bandwidth"]["is_unlimited"] = false;

        $this->expectException(ValidationException::class);
        (new PackageValidator())->validate($payload);
    }

    public function testCirCannotExceedMir(): void
    {
        $payload = $this->validPayload();
        $payload["bandwidth"]["cir_kbps"] = 30000;
        $payload["bandwidth"]["mir_kbps"] = 20000;

        $this->expectException(ValidationException::class);
        (new PackageValidator())->validate($payload);
    }

    public function testItRejectsInvalidOptionalIntegers(): void
    {
        $payload = $this->validPayload();
        $payload["network"]["vlan_id"] = "not-a-vlan";

        $this->expectException(ValidationException::class);
        (new PackageValidator())->validate($payload);
    }

    public function testItRejectsOverlongProfileNamesInsteadOfTruncatingThem(): void
    {
        $payload = $this->validPayload();
        $payload["technical"]["radius_profile"] = str_repeat("x", 151);

        $this->expectException(ValidationException::class);
        (new PackageValidator())->validate($payload);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            "name" => "Home 20 Mbps",
            "code" => "home-20",
            "description" => "Residential plan",
            "category" => "residential",
            "status" => "active",
            "pricing" => [
                "monthly" => 2500,
                "quarterly" => 7000,
                "semi_annual" => 13500,
                "annual" => 26000,
                "installation_charge" => 5000,
                "supports_tax" => true,
                "supports_discount" => true,
            ],
            "bandwidth" => [
                "download_kbps" => 20000,
                "upload_kbps" => 10000,
                "burst_download_kbps" => 25000,
                "burst_upload_kbps" => 12000,
                "cir_kbps" => 5000,
                "mir_kbps" => 25000,
                "data_limit_bytes" => null,
                "is_unlimited" => true,
            ],
            "network" => [
                "pppoe_profile_name" => "home-20",
                "hotspot_profile_name" => null,
                "queue_type" => "pcq",
                "vlan_id" => 100,
                "ip_pool" => "residential-pool",
                "dns_profile" => "default",
            ],
            "customer_rules" => [
                "max_devices" => 5,
                "allows_static_ip" => false,
                "allows_public_ip" => false,
                "allows_dynamic_ip" => true,
                "suspension_policy" => "grace_period",
                "grace_period_days" => 3,
            ],
            "billing" => [
                "default_billing_cycle" => "monthly",
                "auto_renew" => true,
                "invoice_generation_mode" => "advance",
                "supports_late_fee" => true,
            ],
            "technical" => [
                "radius_profile" => "residential",
                "authentication_method" => "pppoe",
                "qos_profile" => "standard",
            ],
        ];
    }
}
