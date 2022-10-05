<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CampaignUpdate
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\GreaterThan('today')]
    public ?\DateTime $sendingDate;

    public function hydrateFromData(array $data): void
    {
        $this->sendingDate = new \DateTime($data['campaign-sending-date']);
    }
}
