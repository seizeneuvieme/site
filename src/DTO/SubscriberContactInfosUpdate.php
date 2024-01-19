<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SubscriberContactInfosUpdate
{
    #[Assert\NotNull]
    #[Assert\Length(min: 3, max: 255)]
    public string $firstname;

    public function hydrateFromData(array $data): void
    {
        $this->firstname = $data['firstname'] ?? '';
    }
}
