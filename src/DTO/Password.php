<?php

namespace App\DTO;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[AppAssert\IsValidPassword]
class Password
{
    #[Assert\NotNull]
    #[Assert\Length(min: 8)]
    public ?string $password;

    public ?string $confirmPassword;

    public function hydrateFromData(array $data)
    {
        $this->password = $data['password'] ?? null;
        $this->confirmPassword = $data['confirm-password'] ?? null;
    }
}