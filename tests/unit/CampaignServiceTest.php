<?php

namespace App\Tests\unit;

use App\DTO\CampaignCreate;
use App\Entity\Campaign;
use App\Entity\Platform;
use App\Entity\Subscriber;
use App\Repository\SubscriberRepository;
use App\Service\CampaignService;
use App\Service\BrevoApiService;
use Brevo\Client\Model\GetSmtpTemplateOverview;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
        $BrevoApiService = $this->getMockBuilder(BrevoApiService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->campaignService = new CampaignService(
            $subscriberRepository,
            $entityManager,
            $BrevoApiService,
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
        $platform = new Platform();
        $platform->setName(Platform::NETFLIX);
        $subscriber->addPlatform($platform);

        // Act
        $params = $this->campaignService->createParams($subscriber);

        // Assert
        $this->assertEquals($subscriber->getFirstname(), $params['FIRSTNAME']);
        $this->assertEquals(true, $params['NETFLIX']);
        $this->assertEquals(false, $params['DISNEY']);
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
        $subscriber->setIsVerified(true);
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
        $BrevoApiService = $this->getMockBuilder(BrevoApiService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->campaignService = new CampaignService(
            $subscriberRepository,
            $entityManager,
            $BrevoApiService,
            $loggerInterface
        );

        $BrevoApiService
            ->expects(self::exactly(2))
            ->method('getTemplate')
            ->willReturn(new GetSmtpTemplateOverview());

        $subscriberRepository
            ->expects(self::once())
            ->method('findBy')
            ->willReturn([$subscriber]);

        $BrevoApiService
            ->expects(self::exactly(2))
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
