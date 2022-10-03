<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class Campaign
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    public ?string $name;

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $templateId;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\GreaterThan('today')]
    public ?\DateTime $sendingDate;

    public function hydrateFromData(array $data)
    {
        $this->name = $data['name'] ?? null;
        $this->templateId = $data['templateId'] ?? null;
        $this->sendingDate = new \DateTime($data['sendingDate']);
    }
}