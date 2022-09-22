<?php

namespace App\Validator;

use App\DTO\Registration;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsValidPasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof IsValidPassword) {
            throw new UnexpectedTypeException($constraint, IsValidPassword::class);
        }

        if (false === $value instanceof(Registration::class)) {
            return;
        }

        if ($value->password !== $value->confirmPassword) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
