<?php

namespace App\Service;

use App\DTO\Registration;
use App\DTO\UserInfos;

class CityService
{
    public function processCityDetails(Registration|UserInfos $details): void
    {
        if (null !== $details->cityDetails) {
            $cityDetails = explode(',', $details->cityDetails);
            if (count($cityDetails) === 3) {
                $details->departmentNumber = trim($cityDetails[0]);
                $details->departmentName = trim($cityDetails[1]);
                $details->region = trim($cityDetails[2]);
            }
        }
    }

}