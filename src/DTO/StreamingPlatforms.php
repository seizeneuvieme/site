<?php

namespace App\DTO;

use App\Validator as AppAssert;

class StreamingPlatforms
{
    #[AppAssert\IsValidPlatform]
    public array $streamingPlatforms;

    public function hydrateFromData(array $data)
    {
        $this->streamingPlatforms = $data['streaming'] ?? [];
    }
}