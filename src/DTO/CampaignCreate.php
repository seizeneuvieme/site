<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CampaignCreate
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    public string $name;

    #[Assert\NotNull]
    #[Assert\Positive]
    public int $templateId;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\GreaterThan('today')]
    public \DateTime $sendingDate;

    public function hydrateFromData(array $data): void
    {
        $this->name        = $data['name'] ?? '';
        $this->templateId  = $data['templateId'] ?? -1;
        $this->sendingDate = new \DateTime($data['sendingDate']);
    }
}
