<?php

namespace Domain\Subscription\Entity;

class StreamingPlatform
{
    public const TNT                 = 'Tnt';
    public const NETFLIX             = 'Netflix';
    public const DISNEY              = 'Disney';
    public const PRIME               = 'Prime';
    public const CANAL               = 'Canal';
    
    public const AVAILABLE_PLATFORMS = [self::TNT, self::NETFLIX, self::DISNEY, self::PRIME, self::CANAL];
    
    private ?int $id = null;
    
    private ?string $name = null;
    

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
}
