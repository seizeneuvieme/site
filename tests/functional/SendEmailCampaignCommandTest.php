<?php

namespace App\Tests\functional;

use App\Repository\CampaignRepository;
use App\Repository\SubscriberRepository;
use App\Service\SendInBlueApiService;
use App\Tests\builder\database\CampaignBuilder;
use App\Tests\builder\database\ChildBuilder;
use App\Tests\builder\database\SubscriberBuilder;
use DateTime;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use SendinBlue\Client\Model\GetSmtpTemplateOverview;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendEmailCampaignCommandTest extends KernelTestCase
{
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
            ->withIsVerified(true)
            ->insert();
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
         * @var SubscriberRepository $subscriberRepository
         */
        $subscriberRepository = $container->get(SubscriberRepository::class);
        $subscriber           = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);

        /**
         * @var Connection $connection
         */
        $connection = $container->get(Connection::class);
        (new ChildBuilder($connection))
            ->withSubscriberId($subscriber->getId())
            ->withBirthDate(new DateTime('-3 years'))
            ->insert();

        /**
         * @var Connection $connection
         */
        $connection = $container->get(Connection::class);
        (new CampaignBuilder($connection))
            ->withName($campaignToSendName)
            ->withSendingDate(new Datetime('today'))
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
            ->withSendingDate(new Datetime('+1 day'))
            ->insert();

        $sendInBlueApiService = $this->createMock(SendInBlueApiService::class);
        $sendInBlueApiService
            ->expects(self::once())
            ->method('getTemplate')
            ->willReturn(
                new GetSmtpTemplateOverview()
            );
        $sendInBlueApiService
            ->expects(self::once())
            ->method('sendTransactionalEmail')
            ->willReturn(true);
        $container->set(SendInBlueApiService::class, $sendInBlueApiService);

        // Act
        $command       = $application->find('app:send:email-campaign');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Assert
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(
            "[INFO] Sending campaign $campaignToSendName...",
            $output
        );
        $this->assertStringContainsString(
            '[INFO] Email(s) sent : 1',
            $output
        );
        $this->assertStringContainsString(
            "[OK] Campaign $campaignToSendName sent!",
            $output
        );
    }
}
