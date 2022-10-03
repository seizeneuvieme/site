<?php

namespace App\Service;

use App\DTO\Campaign AS CampaignDTO;
use App\Entity\Campaign;

class CampaignService
{

    public function createCampaignFromDTO(CampaignDTO $dto): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($dto->name);
        $campaign->setTemplateId($dto->templateId);
        $campaign->setSendingDate($dto->sendingDate);

        return $campaign;
    }
}