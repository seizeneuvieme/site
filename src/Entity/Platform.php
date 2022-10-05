<?php

namespace App\Entity;

use App\Repository\PlatformsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlatformsRepository::class)]
class Platform
{
    public const NETFLIX             = 'Netflix';
    public const DISNEY              = 'Disney';
    public const AVAILABLE_PLATFORMS = [self::NETFLIX, self::DISNEY];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Subscriber::class, mappedBy: 'platforms')]
    private Collection $subscribers;

    public function __construct()
    {
        $this->subscribers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Subscriber>
     */
    public function getSubscribers(): Collection
    {
        return $this->subscribers;
    }

    public function addSubscriber(Subscriber $subscriber): self
    {
        if (!$this->subscribers->contains($subscriber)) {
            $this->subscribers->add($subscriber);
            $subscriber->addPlatform($this);
        }

        return $this;
    }

    public function removeSubscriber(Subscriber $subscriber): self
    {
        if ($this->subscribers->removeElement($subscriber)) {
            $subscriber->removePlatform($this);
        }

        return $this;
    }
}
