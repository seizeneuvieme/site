<?php

namespace App\Validator;

use App\Entity\Platform;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsValidPlatformValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof IsValidPlatform) {
            throw new UnexpectedTypeException($constraint, IsValidPlatform::class);
        }
        if (null === $value || '' === $value) {
            return;
        }

        foreach ($value as $platform) {
            if (false === in_array($platform, Platform::AVAILABLE_PLATFORMS)) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }

    }
}
