<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Validators;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Vendors\DTOs\ContactData;
final class ContactValidator
{
    public function validate(ContactData $data): void
    {
        $errors=[];
        if ($data->name === '' || strlen($data->name)>200) $errors[]=$this->error('name','Contact name is required and must be at most 200 characters.');
        if ($data->email!==null && filter_var($data->email,FILTER_VALIDATE_EMAIL)===false) $errors[]=$this->error('email','Enter a valid email address.');
        if ($data->phone!==null && strlen($data->phone)>50) $errors[]=$this->error('phone','Phone must be at most 50 characters.');
        if ($data->department!==null && strlen($data->department)>120) $errors[]=$this->error('department','Department must be at most 120 characters.');
        if ($errors!==[]) throw new ValidationException($errors);
    }
    /** @return array<string,mixed> */ private function error(string $field,string $detail):array{return ['code'=>'validation_error','detail'=>$detail,'source'=>['pointer'=>'/data/attributes/'.$field]];}
}
