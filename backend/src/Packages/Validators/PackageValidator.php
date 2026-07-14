<?php

declare(strict_types=1);

namespace SkyFi\Packages\Validators;

use SkyFi\Shared\Exceptions\ValidationException;

final class PackageValidator
{
    private const STATUSES = ['draft', 'active', 'inactive', 'archived'];
    private const CATEGORIES = ['residential', 'business', 'corporate', 'enterprise', 'dedicated', 'custom'];
    private const CYCLES = ['monthly', 'quarterly', 'semi_annual', 'annual'];
    private const INVOICE_MODES = ['advance', 'arrears', 'manual'];
    private const SUSPENSION_POLICIES = ['immediate', 'grace_period', 'manual', 'billing_driven'];

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function validate(array $input): array
    {
        $errors = [];
        $requiredString = function (string $key, string $label, int $max) use ($input, &$errors): string {
            $value = is_string($input[$key] ?? null) ? trim($input[$key]) : '';
            if ($value === '') {
                $errors[] = $this->error($key, "$label is required.");
            } elseif (strlen($value) > $max) {
                $errors[] = $this->error($key, "$label must be $max characters or fewer.");
            }
            return $value;
        };
        $name = $requiredString('name', 'Package name', 150);
        $code = strtoupper($requiredString('code', 'Package code', 50));
        if ($code !== '' && preg_match('/^[A-Z0-9][A-Z0-9_-]*$/', $code) !== 1) {
            $errors[] = $this->error('code', 'Package code may contain only letters, numbers, hyphens, and underscores.');
        }
        $category = is_string($input['category'] ?? null) ? strtolower(trim($input['category'])) : '';
        $status = is_string($input['status'] ?? null) ? strtolower(trim($input['status'])) : 'draft';
        if (!in_array($category, self::CATEGORIES, true)) $errors[] = $this->error('category', 'Select a valid package category.');
        if (!in_array($status, self::STATUSES, true)) $errors[] = $this->error('status', 'Select a valid package status.');

        $pricing = $this->section($input, 'pricing');
        $prices = [];
        foreach (self::CYCLES as $cycle) $prices[$cycle] = $this->decimal($pricing, $cycle, 'pricing.' . $cycle, $errors);
        $installation = $this->decimal($pricing, 'installation_charge', 'pricing.installation_charge', $errors);

        $bandwidth = $this->section($input, 'bandwidth');
        $download = $this->positiveInt($bandwidth, 'download_kbps', 'bandwidth.download_kbps', $errors, true);
        $upload = $this->positiveInt($bandwidth, 'upload_kbps', 'bandwidth.upload_kbps', $errors, true);
        $burstDown = $this->positiveInt($bandwidth, 'burst_download_kbps', 'bandwidth.burst_download_kbps', $errors);
        $burstUp = $this->positiveInt($bandwidth, 'burst_upload_kbps', 'bandwidth.burst_upload_kbps', $errors);
        $cir = $this->positiveInt($bandwidth, 'cir_kbps', 'bandwidth.cir_kbps', $errors);
        $mir = $this->positiveInt($bandwidth, 'mir_kbps', 'bandwidth.mir_kbps', $errors);
        $unlimited = $this->bool($bandwidth, 'is_unlimited', true, $errors, 'bandwidth.is_unlimited');
        $dataLimit = $this->positiveInt($bandwidth, 'data_limit_bytes', 'bandwidth.data_limit_bytes', $errors);
        if (!$unlimited && $dataLimit === null) $errors[] = $this->error('bandwidth.data_limit_bytes', 'Data limit is required when unlimited data is disabled.');
        if ($cir !== null && $mir !== null && $cir > $mir) $errors[] = $this->error('bandwidth.cir_kbps', 'CIR cannot exceed MIR.');

        $network = $this->section($input, 'network');
        $vlan = $this->nullableInt($network['vlan_id'] ?? null);
        if ($vlan !== null && ($vlan < 1 || $vlan > 4094)) $errors[] = $this->error('network.vlan_id', 'VLAN must be between 1 and 4094.');
        $rules = $this->section($input, 'customer_rules');
        $maxDevices = $this->positiveInt($rules, 'max_devices', 'customer_rules.max_devices', $errors);
        $grace = $this->nonNegativeInt($rules, 'grace_period_days', 'customer_rules.grace_period_days', $errors, 365);
        $policy = $this->text($rules, 'suspension_policy') ?? 'grace_period';
        if (!in_array($policy, self::SUSPENSION_POLICIES, true)) $errors[] = $this->error('customer_rules.suspension_policy', 'Select a valid suspension policy.');
        $billing = $this->section($input, 'billing');
        $cycle = $this->text($billing, 'default_billing_cycle') ?? 'monthly';
        $invoiceMode = $this->text($billing, 'invoice_generation_mode') ?? 'advance';
        if (!in_array($cycle, self::CYCLES, true)) $errors[] = $this->error('billing.default_billing_cycle', 'Select a valid billing cycle.');
        if (!in_array($invoiceMode, self::INVOICE_MODES, true)) $errors[] = $this->error('billing.invoice_generation_mode', 'Select a valid invoice generation mode.');
        $supportsTax = $this->bool($pricing, 'supports_tax', false, $errors, 'pricing.supports_tax');
        $supportsDiscount = $this->bool($pricing, 'supports_discount', false, $errors, 'pricing.supports_discount');
        $allowsStaticIp = $this->bool($rules, 'allows_static_ip', false, $errors, 'customer_rules.allows_static_ip');
        $allowsPublicIp = $this->bool($rules, 'allows_public_ip', false, $errors, 'customer_rules.allows_public_ip');
        $allowsDynamicIp = $this->bool($rules, 'allows_dynamic_ip', true, $errors, 'customer_rules.allows_dynamic_ip');
        $autoRenew = $this->bool($billing, 'auto_renew', true, $errors, 'billing.auto_renew');
        $supportsLateFee = $this->bool($billing, 'supports_late_fee', false, $errors, 'billing.supports_late_fee');
        if ($errors !== []) throw new ValidationException($errors);

        $technical = $this->section($input, 'technical');
        return [
            'name' => $name, 'code' => $code, 'description' => $this->nullableText($input['description'] ?? null, 5000), 'category' => $category, 'status' => $status,
            'pricing' => [...$prices, 'installation_charge' => $installation, 'supports_tax' => $supportsTax, 'supports_discount' => $supportsDiscount],
            'bandwidth' => ['download_kbps' => $download, 'upload_kbps' => $upload, 'burst_download_kbps' => $burstDown, 'burst_upload_kbps' => $burstUp, 'cir_kbps' => $cir, 'mir_kbps' => $mir, 'data_limit_bytes' => $unlimited ? null : $dataLimit, 'is_unlimited' => $unlimited],
            'network' => ['pppoe_profile_name' => $this->limited($network, 'pppoe_profile_name'), 'hotspot_profile_name' => $this->limited($network, 'hotspot_profile_name'), 'queue_type' => $this->limited($network, 'queue_type', 50), 'vlan_id' => $vlan, 'ip_pool' => $this->limited($network, 'ip_pool'), 'dns_profile' => $this->limited($network, 'dns_profile')],
            'customer_rules' => ['max_devices' => $maxDevices, 'allows_static_ip' => $allowsStaticIp, 'allows_public_ip' => $allowsPublicIp, 'allows_dynamic_ip' => $allowsDynamicIp, 'suspension_policy' => $policy, 'grace_period_days' => $grace],
            'billing' => ['default_billing_cycle' => $cycle, 'auto_renew' => $autoRenew, 'invoice_generation_mode' => $invoiceMode, 'supports_late_fee' => $supportsLateFee],
            'technical' => ['radius_profile' => $this->limited($technical, 'radius_profile'), 'authentication_method' => $this->limited($technical, 'authentication_method', 50), 'qos_profile' => $this->limited($technical, 'qos_profile')],
        ];
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */ private function section(array $input, string $key): array { return is_array($input[$key] ?? null) ? $input[$key] : []; }
    /** @param array<string,mixed> $a @param array<int,array<string,mixed>> $e */ private function decimal(array $a, string $k, string $p, array &$e): string { $v=$a[$k]??0; if (!is_numeric($v) || (float)$v<0 || (float)$v>9999999999.99) { $e[]=$this->error($p,'Enter a valid non-negative amount.'); return '0.00'; } return number_format((float)$v,2,'.',''); }
    /** @param array<string,mixed> $a @param array<int,array<string,mixed>> $e */ private function positiveInt(array $a,string $k,string $p,array &$e,bool $required=false): ?int { $v=$this->nullableInt($a[$k]??null); if ($required && ($v===null || $v<1)) $e[]=$this->error($p,'Enter a value greater than zero.'); elseif ($v!==null && $v<1) $e[]=$this->error($p,'Enter a value greater than zero.'); return $v; }
    /** @param array<string,mixed> $a @param array<int,array<string,mixed>> $e */ private function nonNegativeInt(array $a,string $k,string $p,array &$e,int $max): int { $v=$this->nullableInt($a[$k]??0)??0; if($v<0||$v>$max){$e[]=$this->error($p,"Enter a value between 0 and $max.");return 0;}return $v; }
    private function nullableInt(mixed $v): ?int { return $v===''||$v===null?null:(filter_var($v,FILTER_VALIDATE_INT)!==false?(int)$v:null); }
    /** @param array<string,mixed> $a @param array<int,array<string,mixed>> $e */ private function bool(array $a,string $k,bool $d,array &$e,string $p=''): bool { if(!array_key_exists($k,$a))return $d;if(!is_bool($a[$k])){$e[]=$this->error($p?:$k,'This value must be true or false.');return $d;}return $a[$k]; }
    /** @param array<string,mixed> $a */ private function text(array $a,string $k): ?string { return isset($a[$k])&&is_string($a[$k])?trim($a[$k]):null; }
    /** @param array<string,mixed> $a */ private function limited(array $a,string $k,int $max=150): ?string { return $this->nullableText($a[$k]??null,$max); }
    private function nullableText(mixed $v,int $max): ?string { if(!is_string($v)||trim($v)==='')return null;return substr(trim($v),0,$max); }
    /** @return array<string,mixed> */ private function error(string $key,string $detail): array { return ['code'=>'invalid','detail'=>$detail,'source'=>['pointer'=>'/data/attributes/'.str_replace('.','/',$key)]]; }
}
