<?php

namespace App\DTO;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

class SubscriberChildCreate
{
    #[Assert\NotNull]
    #[Assert\Length(min: 3)]
    public ?string $childFirstname;

    #[AppAssert\HasRightAge]
    public ?\DateTime $childBirthDate;

    public function hydrateFromData(array $data): void
    {
        $this->childFirstname = $data['child-firstname'] ?? null;
        $this->childBirthDate = $data['child-birth-date'] ? new \DateTime($data['child-birth-date']) : null;
    }
}
