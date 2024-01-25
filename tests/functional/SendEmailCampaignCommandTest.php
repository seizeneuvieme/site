<?php

namespace App\Tests\functional;

use App\Repository\CampaignRepository;
use App\Repository\SubscriberRepository;
use App\Service\BrevoApiService;
use App\Tests\builder\database\CampaignBuilder;
use App\Tests\builder\database\SubscriberBuilder;
use Brevo\Client\Model\GetSmtpTemplateOverview;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendEmailCampaignCommandTest extends KernelTestCase
{
    private readonly TestHandler $logger;

    public function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();
        /**
         * @var EntityManagerInterface $entityManager
         */
        $entityManager = $container->get(EntityManagerInterface::class);
        $purger        = new ORMPurger($entityManager);
        $purger->purge();
        /**
         * @var Connection $connection
         */
        $connection = $container->get(Connection::class);
        (new SubscriberBuilder($connection))
            ->withEmail('marty@mcfly.com')
            ->withRoles(['ROLE_ADMIN'])
            ->withPlatforms(['Disney', 'Tnt'])
            ->withIsVerified(true)
            ->insert();
        (new SubscriberBuilder($connection))
            ->withEmail('doc@doc.com')
            ->withIsVerified(true)
            ->insert();

        $log          = new Logger('test');
        $this->logger = new TestHandler();
        $log->pushHandler($this->logger);
    }

    /**
     * @test
     */
    public function it_executes_command(): void
    {
        // Arrange
        $kernel      = self::bootKernel();
        $application = new Application($kernel);

        $faker               = Factory::create();
        $campaignToSendName  = $faker->word;
        $pendingCampaignName = $faker->word;
        $container           = static::getContainer();

        /**
         * @var Connection $connection
         */
        $connection = $container->get(Connection::class);
        (new CampaignBuilder($connection))
            ->withName($campaignToSendName)
            ->withSendingDate(new \DateTime('today'))
            ->insert();

        /**
         * @var SubscriberRepository $subscriberRepository
         */
        $campaignRepository = $container->get(CampaignRepository::class);
        $campaign           = $campaignRepository->findOneBy([
            'name' => $campaignToSendName,
        ]);

        (new CampaignBuilder($connection))
            ->withName($pendingCampaignName)
            ->withSendingDate(new \DateTime('+1 day'))
            ->insert();

        $BrevoApiService = $this->createMock(BrevoApiService::class);
        $BrevoApiService
            ->expects(self::exactly(2))
            ->method('getTemplate')
            ->willReturn(
                new GetSmtpTemplateOverview()
            );
        $BrevoApiService
            ->expects(self::exactly(2))
            ->method('sendTransactionalEmail')
            ->willReturn(true);
        $container->set(BrevoApiService::class, $BrevoApiService);

        // Act
        $command       = $application->find('app:send:email-campaign');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Assert
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->logger->hasInfo(
            [
                'message' => 'BEGIN_SEND_EMAIL_CAMPAIGN_COMMAND',
                'context' => [],
            ]
        );
        $this->logger->hasInfo(
            [
                'message' => 'PROCESS_CAMPAIGNS',
                'context' => [
                    'campaignsToSend' => 1,
                ],
            ]
        );
        $this->logger->hasInfo(
            [
                'message' => 'PROCESS_CAMPAIGN',
                'context' => [
                    'campaignId'   => $campaign->getId(),
                    'campaignName' => $campaign->getName(),
                ],
            ]
        );
        $this->logger->hasInfo(
            [
                'message' => 'CAMPAIGN_SENT',
                'context' => [
                    'campaignId'   => $campaign->getId(),
                    'campaignName' => $campaign->getName(),
                    'emailSent'    => 1,
                    'emailError'   => 0,
                ],
            ]
        );
        $this->logger->hasInfo(
            [
                'message' => 'CAMPAIGN_PROCESSED',
                'context' => [
                    'campaignId'   => $campaign->getId(),
                    'campaignName' => $campaign->getName(),
                ],
            ]
        );
        $this->logger->hasInfo(
            [
                'message' => 'END_SEND_EMAIL_CAMPAIGN_COMMAND',
                'context' => [],
            ]
        );
    }
}
