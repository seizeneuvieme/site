<?php

namespace Domain\Subscription\Entity;

class Subscriber
{
    private string $email;
    private string $firstname;
    private string $password;
    
    /**
     * @var StreamingPlatform[] $streamingPlatforms
     */
    private array $streamingPlatforms;
    private array $roles;
    private bool $isVerified;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getStreamingPlatforms(): array
    {
        return $this->streamingPlatforms;
    }

    public function setStreamingPlatforms(array $streamingPlatforms): void
    {
        $this->streamingPlatforms = $streamingPlatforms;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): void
    {
        $this->isVerified = $isVerified;
    }
}
