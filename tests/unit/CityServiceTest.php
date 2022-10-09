<?php

namespace App\Tests\unit;

use App\DTO\SubscriberContactInfosUpdate;
use App\Service\CityService;
use PHPUnit\Framework\TestCase;

class CityServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_converts_city_details(): void
    {
        // Arrange
        $subscriberContactInfosUpdate              = new SubscriberContactInfosUpdate();
        $subscriberContactInfosUpdate->cityDetails = '34, Hérault, Occitanie';

        // Act
        $cityService = new CityService();
        $cityService->processCityDetails($subscriberContactInfosUpdate);

        // Assert
        $this->assertEquals('34', $subscriberContactInfosUpdate->departmentNumber);
        $this->assertEquals('Hérault', $subscriberContactInfosUpdate->departmentName);
        $this->assertEquals('Occitanie', $subscriberContactInfosUpdate->region);
    }
}
