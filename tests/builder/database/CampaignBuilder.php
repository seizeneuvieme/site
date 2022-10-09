<?php

namespace App\Tests\builder\database;

use App\Entity\Campaign;
use DateTime;
use Doctrine\DBAL\Connection;
use Faker\Factory;

class CampaignBuilder
{
    private int $id;
    private string $name;
    private int $templateId;
    private \DateTimeInterface $sendingDate;
    private string $state;
    private int $numberSent;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        private readonly Connection $connection,
    ) {
        $faker             = Factory::create();
        $this->id          = $faker->randomNumber();
        $this->name        = $faker->word;
        $this->templateId  = $faker->randomNumber();
        $this->sendingDate = $faker->dateTimeBetween('now', '+10 days');
        $this->state       = Campaign::DRAFT_STATE;
        $this->numberSent  = 0;
        $this->createdAt   = new DateTime('NOW');
        $this->updatedAt   = new DateTime('NOW');
    }

    public function fake(
        int $id,
        string $name,
        int $templateId,
        \DateTimeInterface $sendingDate,
        string $state,
        int $numberSent,
    ): self {
        $this->id          = $id;
        $this->name        = $name;
        $this->templateId  = $templateId;
        $this->sendingDate = $sendingDate;
        $this->state       = $state;
        $this->numberSent  = $numberSent;

        return $this;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function insert(): self
    {
        $this->connection->insert('public.campaign', [
            'id'           => $this->id,
            'name'         => $this->name,
            'template_id'  => $this->templateId,
            'sending_date' => $this->sendingDate->format('Y-m-d'),
            'state'        => $this->state,
            'number_sent'  => $this->numberSent,
            'created_at'   => $this->createdAt->format('d-m-Y'),
            'update_at'    => $this->updatedAt->format('d-m-Y'),
        ]);

        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withSendingDate(DateTime $sendingDate): self
    {
        $this->sendingDate = $sendingDate;

        return $this;
    }
}
