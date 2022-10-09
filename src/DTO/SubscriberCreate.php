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

    #[Assert\NotNull]
    #[Assert\Length(min: 3, max: 255)]
    public string $childFirstname;

    #[AppAssert\HasRightAge]
    public \DateTime $childBirthDate;

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
        $this->childFirstname     = $data['child-firstname'] ?? '';
        $this->childBirthDate     = $data['child-birth-date'] ? new \DateTime($data['child-birth-date']) : new \DateTime('NOW');
        $this->streamingPlatforms = $data['streaming'] ?? [];
    }
}
