<?php

namespace App\DTO;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[AppAssert\IsValidPassword]
class SubscriberCreate
{
    #[Assert\NotNull]
    #[Assert\Email]
    #[AppAssert\IsUniqueEmail]
    public ?string $email;

    #[Assert\NotNull]
    #[Assert\Length(min: 8)]
    public ?string $password;

    public ?string $confirmPassword;

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

    #[Assert\NotNull]
    #[Assert\Length(min: 3)]
    public ?string $childFirstname;

    #[AppAssert\HasRightAge]
    public ?\DateTime $childBirthDate;

    #[AppAssert\IsValidPlatform]
    public array $streamingPlatforms;

    public function hydrateFromData(array $data)
    {
        $this->email = $data['email'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->confirmPassword = $data['confirm-password'] ?? null;
        $this->firstname = $data['firstname'] ?? null;
        $this->city = $data['city'];
        $this->cityDetails = $data['city-details'] ?? null;
        $this->childFirstname = $data['child-firstname'] ?? null;
        $this->childBirthDate = $data['child-birth-date'] ? new \DateTime($data['child-birth-date']) : null;
        $this->streamingPlatforms = $data['streaming'] ?? [];
    }
}