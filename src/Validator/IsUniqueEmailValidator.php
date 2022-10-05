<?php

namespace App\Validator;

use App\Repository\SubscriberRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsUniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly SubscriberRepository $subscriberRepository
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsUniqueEmail) {
            throw new UnexpectedTypeException($constraint, IsUniqueEmail::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $subscriber = $this->subscriberRepository->findOneBy([
            'email' => $value,
        ]);

        if ($subscriber !== null) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
