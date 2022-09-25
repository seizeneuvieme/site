<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserInfos
{
    #[Assert\NotNull]
    #[Assert\Length(min: 3)]
    public ?string $firstname;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public ?string $city;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public ?string $cityDetails;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public ?string $departmentNumber = null;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public ?string $departmentName = null;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public ?string $region = null;

    public function hydrateFromData(array $data)
    {
        $this->firstname = $data['firstname'] ?? null;
        $this->city = $data['city'];
        $this->cityDetails = $data['city-details'] ?? null;
    }
}