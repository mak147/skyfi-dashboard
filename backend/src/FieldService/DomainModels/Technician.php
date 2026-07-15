<?php
declare(strict_types=1);
namespace SkyFi\FieldService\DomainModels;
final class Technician { public function __construct(private readonly array $attributes){} public function id():int{return (int)$this->attributes['id'];} public function toArray():array{return $this->attributes;} }
