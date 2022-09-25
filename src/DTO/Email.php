<?php

namespace App\DTO;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

class Email
{
    #[Assert\NotNull]
    #[Assert\Email]
    #[AppAssert\IsUniqueEmail]
    public ?string $email;

    public function hydrateFromData(array $data)
    {
        $this->email = $data['email'] ?? null;
    }
}