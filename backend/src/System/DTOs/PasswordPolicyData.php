<?php declare(strict_types=1);
namespace SkyFi\System\DTOs;
final class PasswordPolicyData { public function __construct(public readonly array $values){} public static function fromArray(array $input): self { $data=$input['data']['attributes'] ?? $input; return new self(is_array($data)?$data:[]); }}
