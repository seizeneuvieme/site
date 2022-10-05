<?php

namespace App\Service;

use App\DTO\SubscriberCreate;
use App\DTO\SubscriberContactInfosUpdate;

class CityService
{
    public function processCityDetails(SubscriberCreate|SubscriberContactInfosUpdate $subscriber): void
    {
        if (null !== $subscriber->cityDetails) {
            $cityDetails = explode(',', $subscriber->cityDetails);
            if (count($cityDetails) === 3) {
                $subscriber->departmentNumber = trim($cityDetails[0]);
                $subscriber->departmentName = trim($cityDetails[1]);
                $subscriber->region = trim($cityDetails[2]);
            }
        }
    }

}