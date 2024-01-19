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

    #[AppAssert\IsValidPlatform]
    public array $streamingPlatforms;

    public function hydrateFromData(array $data): void
    {
        $this->email              = $data['email'] ?? '';
        $this->password           = $data['password'] ?? '';
        $this->confirmPassword    = $data['confirm-password'] ?? '';
        $this->firstname          = $data['firstname'] ?? '';
        $this->streamingPlatforms = $data['streaming'] ?? [];
    }
}
