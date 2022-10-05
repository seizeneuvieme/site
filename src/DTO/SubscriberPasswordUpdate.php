<?php

namespace App\DTO;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[AppAssert\IsValidPassword]
class SubscriberPasswordUpdate
{
    #[Assert\NotNull]
    #[Assert\Length(min: 8)]
    public string $password;

    public string $confirmPassword;

    public function hydrateFromData(array $data): void
    {
        $this->password        = $data['password'] ?? '';
        $this->confirmPassword = $data['confirm-password'] ?? '';
    }
}
