<?php

namespace App\DTO;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[AppAssert\IsValidPassword]
class SubscriberCreate
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[AppAssert\IsUniqueEmail]
    public string $email;

    #[Assert\NotNull]
    #[Assert\Length(min: 8)]
    public string $password;

    public string $confirmPassword;

    #[Assert\NotNull]
    #[Assert\Length(min: 3, max: 255)]
    public string $firstname;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public string $city;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public string $cityDetails;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public string $departmentNumber;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public string $departmentName;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public string $region;

    #[AppAssert\IsValidPlatform]
    public array $streamingPlatforms;

    public function hydrateFromData(array $data): void
    {
        $this->email              = $data['email'] ?? '';
        $this->password           = $data['password'] ?? '';
        $this->confirmPassword    = $data['confirm-password'] ?? '';
        $this->firstname          = $data['firstname'] ?? '';
        $this->city               = $data['city'] ?? '';
        $this->cityDetails        = $data['city-details'] ?? '';
        $this->streamingPlatforms = $data['streaming'] ?? [];
    }
}
