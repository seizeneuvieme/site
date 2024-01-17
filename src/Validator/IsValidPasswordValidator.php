<?php

namespace App\Validator;

use App\DTO\SubscriberCreate;
use App\DTO\SubscriberPasswordUpdate;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsValidPasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsValidPassword) {
            throw new UnexpectedTypeException($constraint, IsValidPassword::class);
        }

        if (
            $value instanceof (SubscriberCreate::class) === false
            && $value instanceof (SubscriberPasswordUpdate::class) === false
        ) {
            return;
        }

        /**
         * @var SubscriberCreate|SubscriberPasswordUpdate $value
         */
        if ($value->password !== $value->confirmPassword) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
