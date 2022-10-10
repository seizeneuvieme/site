<?php

namespace App\Tests\unit;

use App\DTO\CampaignCreate;
use App\Entity\Campaign;
use App\Entity\Child;
use App\Entity\Platform;
use App\Entity\Subscriber;
use App\Repository\SubscriberRepository;
use App\Service\CampaignService;
use App\Service\SendInBlueApiService;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SendinBlue\Client\Model\GetSmtpTemplateOverview;

class CampaignServiceTest extends TestCase
{
    private CampaignService $campaignService;

    public function setUp(): void
    {
        $subscriberRepository = $this->getMockBuilder(SubscriberRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sendInBlueApiService = $this->getMockBuilder(SendInBlueApiService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->campaignService = new CampaignService(
            $subscriberRepository,
            $entityManager,
            $sendInBlueApiService,
            $loggerInterface
        );
    }

    /**
     * @test
     */
    public function it_creates_campaign_from_dto(): void
    {
        // Arrange
        $faker                       = Factory::create();
        $campaignCreate              = new CampaignCreate();
        $campaignCreate->name        = $faker->word;
        $campaignCreate->templateId  = $faker->randomNumber();
        $campaignCreate->sendingDate = $faker->dateTimeBetween('today', '+30 days');

        // Act
        $campaign = $this->campaignService->createCampaignFromDTO($campaignCreate);

        // Assert
        $this->assertEquals($campaignCreate->name, $campaign->getName());
        $this->assertEquals($campaignCreate->templateId, $campaign->getTemplateId());
        $this->assertEquals($campaignCreate->sendingDate->format('d-m-Y'), $campaign->getSendingDate()->format('d-m-Y'));
        $this->assertEquals(Campaign::DRAFT_STATE, $campaign->getState());
        $this->assertEquals(0, $campaign->getNumberSent());
    }

    /**
     * @test
     */
    public function it_creates_params(): void
    {
        // Arrange
        $faker      = Factory::create();
        $subscriber = new Subscriber();
        $subscriber->setFirstname($faker->firstName);
        $subscriber->setCity($faker->city);
        $subscriber->setDepartmentNumber($faker->numberBetween(10, 95));
        $subscriber->setDepartmentName($faker->word);
        $subscriber->setRegion($faker->word);
        $childOne = new Child();
        $childOne->setFirstname($faker->firstName);
        $childOne->setBirthDate($faker->dateTimeBetween('-3 years - 1 day', '-3 years - 1 day'));
        $childTwo = new Child();
        $childTwo->setFirstname($faker->firstName);
        $childTwo->setBirthDate($faker->dateTimeBetween('-3 years - 1 day', '-3 years - 1 day'));
        $childThree = new Child();
        $childThree->setFirstname($faker->firstName);
        $childThree->setBirthDate($faker->dateTimeBetween('-12 years - 1 day', '-12 years - 1 day'));
        $subscriber->addChild($childOne);
        $subscriber->addChild($childTwo);
        $subscriber->addChild($childThree);
        $platform = new Platform();
        $platform->setName(Platform::NETFLIX);
        $subscriber->addPlatform($platform);

        // Act
        $params = $this->campaignService->createParams($subscriber);

        // Assert
        $this->assertEquals($subscriber->getFirstname(), $params['FIRSTNAME']);
        $this->assertEquals($subscriber->getCity(), $params['CITY']);
        $this->assertEquals($subscriber->getDepartmentNumber(), $params['DEPARTMENT_NUMBER']);
        $this->assertEquals($subscriber->getDepartmentName(), $params['DEPARTMENT_NAME']);
        $this->assertEquals($subscriber->getRegion(), $params['REGION']);
        $this->assertEquals(true, $params['NETFLIX']);
        $this->assertEquals(false, $params['DISNEY']);
        $this->assertEquals(" ✅ {$childOne->getFirstname()} ✅ {$childTwo->getFirstname()}", $params['AGE_3']);
        $this->assertEquals('', $params['AGE_4']);
        $this->assertEquals('', $params['AGE_5']);
        $this->assertEquals('', $params['AGE_6']);
        $this->assertEquals('', $params['AGE_7']);
        $this->assertEquals('', $params['AGE_8']);
        $this->assertEquals('', $params['AGE_9']);
        $this->assertEquals('', $params['AGE_10']);
        $this->assertEquals('', $params['AGE_11']);
        $this->assertEquals(" ✅ {$childThree->getFirstname()}", $params['AGE_12']);
    }

    /**
     * @test
     */
    public function it_processes_campaign(): void
    {
        // Arrange
        $faker      = Factory::create();
        $subscriber = new Subscriber();
        $subscriber->setEmail($faker->email);
        $subscriber->setFirstname($faker->firstName);
        $subscriber->setCity($faker->city);
        $subscriber->setIsVerified(true);
        $subscriber->setDepartmentNumber($faker->numberBetween(10, 95));
        $subscriber->setDepartmentName($faker->word);
        $subscriber->setRegion($faker->word);
        $childOne = new Child();
        $childOne->setFirstname($faker->firstName);
        $childOne->setBirthDate($faker->dateTimeBetween('-3 years - 1 day', '-3 years - 1 day'));
        $childTwo = new Child();
        $childTwo->setFirstname($faker->firstName);
        $childTwo->setBirthDate($faker->dateTimeBetween('-3 years - 1 day', '-3 years - 1 day'));
        $childThree = new Child();
        $childThree->setFirstname($faker->firstName);
        $childThree->setBirthDate($faker->dateTimeBetween('-12 years - 1 day', '-12 years - 1 day'));
        $subscriber->addChild($childOne);
        $subscriber->addChild($childTwo);
        $subscriber->addChild($childThree);
        $platform = new Platform();
        $platform->setName(Platform::NETFLIX);
        $subscriber->addPlatform($platform);

        $campaign = new Campaign();
        $campaign->setName($faker->word);
        $campaign->setTemplateId($faker->randomNumber());
        $campaign->setState(Campaign::DRAFT_STATE);
        $campaign->setNumberSent(0);

        $subscriberRepository = $this->getMockBuilder(SubscriberRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sendInBlueApiService = $this->getMockBuilder(SendInBlueApiService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->campaignService = new CampaignService(
            $subscriberRepository,
            $entityManager,
            $sendInBlueApiService,
            $loggerInterface
        );

        $sendInBlueApiService
            ->expects(self::once())
            ->method('getTemplate')
            ->willReturn(new GetSmtpTemplateOverview());

        $subscriberRepository
            ->expects(self::once())
            ->method('findBy')
            ->willReturn([$subscriber]);

        $sendInBlueApiService
            ->expects(self::once())
            ->method('sendTransactionalEmail')
            ->willReturn(true);

        $entityManager
            ->expects(self::once())
            ->method('flush');

        // Act
        $this->campaignService->processCampaign($campaign);

        // Assert
        $this->assertEquals(Campaign::SENT_STATE, $campaign->getState());
        $this->assertEquals(1, $campaign->getNumberSent());
    }
}
