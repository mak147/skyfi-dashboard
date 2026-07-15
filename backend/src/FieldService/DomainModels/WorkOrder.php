<?php
declare(strict_types=1);
namespace SkyFi\FieldService\DomainModels;
final class WorkOrder { public function __construct(private readonly array $attributes){} public function id():int{return (int)$this->attributes['id'];} public function status():string{return (string)$this->attributes['status'];} public function toArray():array{return $this->attributes;} }
