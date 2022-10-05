<?php

namespace App\Service;

use App\DTO\SubscriberContactInfosUpdate;
use App\DTO\SubscriberCreate;

class CityService
{
    public function processCityDetails(SubscriberCreate|SubscriberContactInfosUpdate $subscriber): void
    {
        if ($subscriber->cityDetails !== null) {
            $cityDetails = explode(',', $subscriber->cityDetails);
            if (count($cityDetails) === 3) {
                $subscriber->departmentNumber = trim($cityDetails[0]);
                $subscriber->departmentName   = trim($cityDetails[1]);
                $subscriber->region           = trim($cityDetails[2]);
            }
        }
    }
}
