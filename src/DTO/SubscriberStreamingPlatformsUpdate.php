<?php

namespace App\DTO;

use App\Validator as AppAssert;

class SubscriberStreamingPlatformsUpdate
{
    #[AppAssert\IsValidPlatform]
    public array $streamingPlatforms;

    public function hydrateFromData(array $data): void
    {
        $this->streamingPlatforms = $data['streaming'] ?? [];
    }
}
