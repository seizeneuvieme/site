<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SubscriberContactInfosUpdate
{
    #[Assert\NotNull]
    #[Assert\Length(min: 3)]
    public string $firstname;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public string $city;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public string $cityDetails;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public string $departmentNumber = '';

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public string $departmentName = '';

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public string $region = '';

    public function hydrateFromData(array $data): void
    {
        $this->firstname   = $data['firstname'] ?? '';
        $this->city        = $data['city'];
        $this->cityDetails = $data['city-details'] ?? '';
    }
}