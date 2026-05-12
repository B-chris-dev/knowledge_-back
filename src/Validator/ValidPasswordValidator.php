<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidPasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidPassword) {
            throw new UnexpectedTypeException($constraint, ValidPassword::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        // Vérifications
        $errors = [];

        // Minimum 12 caractères
        if (strlen($value) < 12) {
            $errors[] = 'au minimum 12 caractères';
        }

        // Au moins une majuscule
        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = 'une majuscule';
        }

        // Au moins un chiffre
        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = 'un chiffre';
        }

        // Au moins un caractère spécial
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $value)) {
            $errors[] = 'un caractère spécial';
        }

        if (!empty($errors)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}