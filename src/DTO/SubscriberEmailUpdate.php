<?php

namespace App\DTO;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

class SubscriberEmailUpdate
{
    #[Assert\NotNull]
    #[Assert\Email]
    #[AppAssert\IsUniqueEmail]
    public string $email;

    public function hydrateFromData(array $data): void
    {
        $this->email = $data['email'] ?? '';
    }
}
