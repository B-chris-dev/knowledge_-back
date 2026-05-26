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

        // Validate the password strength requirements.
        $errors = [];

        // At least 12 characters
        if (strlen($value) < 12) {
            $errors[] = 'at least 12 characters';
        }

        // At least one uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = 'an uppercase letter';
        }

        // At least one digit
        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = 'a number';
        }

        // At least one special character
        if (!preg_match("/[!@#\$%\^&\*()_+\-=\[\]{};:'\",.<>?\/\\|`~]/", $value)) {
            $errors[] = 'a special character';
        }

        if (!empty($errors)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}