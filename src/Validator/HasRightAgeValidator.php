<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class HasRightAgeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof HasRightAge) {
            throw new UnexpectedTypeException($constraint, HasRightAge::class);
        }

        if (null === $value || '' === $value ) {
            return;
        }

        $age = date_diff($value, date_create(date("Y-m-d")));

        if ((int)$age->format('%y') < 3 || (int)$age->format('%y') > 12) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
