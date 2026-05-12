<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidPassword extends Constraint
{
    public string $message = 'Le mot de passe doit contenir au minimum 12 caractères, une majuscule, un chiffre et un caractère spécial.';

    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return ValidPasswordValidator::class;
    }
}