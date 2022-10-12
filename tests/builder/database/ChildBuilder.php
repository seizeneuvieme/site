<?php

namespace App\Tests\builder\database;

use DateTime;
use Doctrine\DBAL\Connection;
use Faker\Factory;

class ChildBuilder
{
    private int $id;
    private int $subscriberId;
    private string $firstname;
    private \DateTimeInterface $birthDate;

    public function __construct(
        private readonly Connection $connection,
    ) {
        $faker              = Factory::create();
        $this->id           = $faker->randomNumber();
        $this->subscriberId = $faker->randomNumber();
        $this->firstname    = $faker->word();
        $this->birthDate    = $faker->dateTimeBetween('-12 years', '-3 years');
    }

    public function fake(
        int $id,
        int $subscriberId,
        string $firstname,
        \DateTimeInterface $birthDate,
    ): self {
        $this->id           = $id;
        $this->subscriberId = $subscriberId;
        $this->firstname    = $firstname;
        $this->birthDate    = $birthDate;

        return $this;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function insert(): self
    {
        $this->connection->insert('child', [
            'id'            => $this->id,
            'subscriber_id' => $this->subscriberId,
            'firstname'     => $this->firstname,
            'birth_date'    => $this->birthDate->format('Y-m-d'),
        ]);

        return $this;
    }

    public function withSubscriberId(int $subscriberId): self
    {
        $this->subscriberId = $subscriberId;

        return $this;
    }

    public function withBirthDate(DateTime $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }
}
