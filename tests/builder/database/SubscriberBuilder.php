<?php

namespace App\Tests\builder\database;

use Doctrine\DBAL\Connection;
use Faker\Factory;

class SubscriberBuilder
{
    private int $id;
    private string $email;
    private string|false $roles;
    private string $password;
    private string $firstname;
    private array $platforms;

    private bool $isVerified;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        private readonly Connection $connection,
    ) {
        $faker            = Factory::create();
        $this->id         = $faker->randomNumber();
        $this->email      = 'marty@mcfly.com';
        $this->roles      = json_encode(['ROLE_ADMIN']);
        $this->password   = $faker->password;
        $this->firstname  = $faker->firstName;
        $this->platforms  = [];
        $this->isVerified = false;
        $this->createdAt  = new \DateTime('NOW');
        $this->updatedAt  = new \DateTime('NOW');
    }

    public function fake(
        int $id,
        string $email,
        string $roles,
        string $password,
        string $firstName,
        bool $isVerified
    ): self {
        $this->id         = $id;
        $this->email      = $email;
        $this->roles      = $roles;
        $this->password   = $password;
        $this->firstname  = $firstName;
        $this->isVerified = $isVerified;
        $this->createdAt  = new \DateTime('NOW');
        $this->updatedAt  = new \DateTime('NOW');

        return $this;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function insert(): self
    {
        $this->connection->insert('subscriber', [
            'id'          => $this->id,
            'email'       => $this->email,
            'roles'       => $this->roles,
            'password'    => $this->password,
            'firstname'   => $this->firstname,
            'is_verified' => $this->isVerified === true ? 1 : 0,
            'created_at'  => $this->createdAt->format('y-m-d'),
            'updated_at'  => $this->updatedAt->format('y-m-d'),
        ]);

        $faker = Factory::create();
        foreach ($this->platforms as $platform) {
            $platformId = $faker->randomNumber();
            $this->connection->insert('platform', [
                'id'   => $platformId,
                'name' => $platform,
            ]);
            $this->connection->insert('subscriber_platform', [
                'subscriber_id' => $this->id,
                'platform_id'   => $platformId,
            ]);
        }

        return $this;
    }

    public function withEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function withPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function withIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function withRoles(array $roles): self
    {
        $this->roles = json_encode($roles);

        return $this;
    }

    public function withPlatforms(array $platforms): self
    {
        $this->platforms = $platforms;

        return $this;
    }
}
