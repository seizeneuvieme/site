<?php

namespace App\Tests\functional;

use App\Repository\CampaignRepository;
use App\Repository\SubscriberRepository;
use App\Service\SendInBlueApiService;
use App\Tests\builder\database\CampaignBuilder;
use App\Tests\builder\database\SubscriberBuilder;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use SendinBlue\Client\Model\GetSmtpTemplateOverview;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

class BackofficeControllerTest extends AbstractWebTestCase
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
            ->withRoles(['ROLE_ADMIN'])
            ->insert();
    }

    /**
     * @test
     */
    public function it_gets_backoffice(): void
    {
        // Arrange
        $container = $this->client->getContainer();
        /**
         * @var SubscriberRepository $subscriberRepository
         */
        $subscriberRepository = $container->get(SubscriberRepository::class);
        $subscriber           = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);
        $this->client->loginUser($subscriber);

        // Act
        $this->client->request('GET', '/passage34/');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', '🔒 Backoffice');
    }

    /**
     * @test
     */
    public function it_does_not_get_backoffice_if_not_logged(): void
    {
        // Act
        $this->client->request('GET', '/passage34/');
        $this->client->followRedirect();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Connexion');
    }

    /**
     * @test
     */
    public function it_does_not_get_backoffice_if_not_role_admin(): void
    {
        // Arrange
        $container = $this->client->getContainer();
        /**
         * @var Connection $connection
         */
        $connection = $container->get(Connection::class);
        (new SubscriberBuilder($connection))
            ->withEmail('not@admin.com')
            ->withRoles([])
            ->insert();
        /**
         * @var SubscriberRepository $subscriberRepository
         */
        $subscriberRepository = $container->get(SubscriberRepository::class);
        $subscriber           = $subscriberRepository->findOneBy([
            'email' => 'not@admin.com',
        ]);
        $this->client->loginUser($subscriber);

        // Act
        $this->client->request('GET', '/passage34/');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function it_adds_campaign(): void
    {
        // Arrange
        $faker                = Factory::create();
        $container            = $this->client->getContainer();
        $sendInBlueApiService = $this->createMock(SendInBlueApiService::class);
        $campaignName         = $faker->word;
        $sendInBlueApiService
            ->method('getTemplate')
            ->willReturn(new GetSmtpTemplateOverview([
                'id'   => $faker->randomNumber(),
                'name' => $campaignName,
            ]));
        $container->set(SendInBlueApiService::class, $sendInBlueApiService);
        /**
         * @var SubscriberRepository $subscriberRepository
         */
        $subscriberRepository = $container->get(SubscriberRepository::class);
        $subscriber           = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);
        $this->loginUser($subscriber);

        $tokenId   = 'add-campaign';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        // Act
        $this->client->request(
            'POST',
            '/passage34/ajouter/campagne',
            [
                'template-id'  => $faker->randomNumber(),
                'sending-date' => $faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
                'token'        => $csrfToken,
            ]
        );
        $this->client->followRedirect();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success > p', "Campagne $campaignName créée 🎉");
    }

    /**
     * @test
     *
     * @dataProvider invalidCampaignProvider
     */
    public function it_does_not_add_campaign_if_invalid(array $invalidFields, string $selector, string $message): void
    {
        // Arrange
        $faker                = Factory::create();
        $container            = $this->client->getContainer();
        $sendInBlueApiService = $this->createMock(SendInBlueApiService::class);
        $campaignName         = $faker->word;
        $sendInBlueApiService
            ->method('getTemplate')
            ->willReturn(new GetSmtpTemplateOverview([
                'id'   => $faker->randomNumber(),
                'name' => $campaignName,
            ]));
        $container->set(SendInBlueApiService::class, $sendInBlueApiService);
        /**
         * @var SubscriberRepository $subscriberRepository
         */
        $subscriberRepository = $container->get(SubscriberRepository::class);
        $subscriber           = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);
        $this->loginUser($subscriber);

        $tokenId   = 'add-campaign';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        // Act
        $this->client->request(
            'POST',
            '/passage34/ajouter/campagne',
            array_merge(
                $invalidFields,
                [
                    'token' => $csrfToken,
                ]
            )
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains($selector, $message);
    }

    public function invalidCampaignProvider()
    {
        $faker = Factory::create();

        return [
            'invalid sending date' => [
                [
                    'template-id'           => $faker->randomNumber(),
                    'campaign-sending-date' => $faker->dateTimeBetween('-30 days', '- 1 day')->format('Y-m-d'),
                ],
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_updates_campaign(): void
    {
        // Arrange
        $faker        = Factory::create();
        $campaignName = $faker->word;
        $container    = $this->client->getContainer();
        /**
         * @var Connection $connection
         */
        $connection = $container->get(Connection::class);
        (new CampaignBuilder($connection))
            ->withName($campaignName)
            ->insert();

        // Act
        /**
         * @var CampaignRepository $campaignRepository
         */
        $campaignRepository = $container->get(CampaignRepository::class);
        $campaign           = $campaignRepository->findOneBy([
            'name' => $campaignName,
        ]);

        /**
         * @var SubscriberRepository $subscriberRepository
         */
        $subscriberRepository = $container->get(SubscriberRepository::class);
        $subscriber           = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);
        $this->loginUser($subscriber);

        $tokenId   = 'update-campaign';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        $newDate = $faker->dateTimeBetween('+1 day', '+30 days');
        $this->client->request(
            'POST',
            '/passage34/modifier/campagne/'.$campaign->getId(),
            [
                'campaign-sending-date' => $newDate->format('Y-m-d'),
                'token'                 => $csrfToken,
            ]
        );
        $campaign = $campaignRepository->findOneBy([
            'name' => $campaignName,
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success > p', "La campagne $campaignName a bien été reprogrammée pour le {$newDate->format('d/m/Y')} 🎉");
        $this->assertEquals($newDate->format('Y-m-d'), $campaign->getSendingDate()->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function it_does_not_update_campaign_if_sending_date_is_invalid(): void
    {
        // Arrange
        $faker        = Factory::create();
        $campaignName = $faker->word;
        $container    = $this->client->getContainer();
        /**
         * @var Connection $connection
         */
        $connection = $container->get(Connection::class);
        (new CampaignBuilder($connection))
            ->withName($campaignName)
            ->insert();

        // Act
        /**
         * @var CampaignRepository $campaignRepository
         */
        $campaignRepository = $container->get(CampaignRepository::class);
        $campaign           = $campaignRepository->findOneBy([
            'name' => $campaignName,
        ]);

        /**
         * @var SubscriberRepository $subscriberRepository
         */
        $subscriberRepository = $container->get(SubscriberRepository::class);
        $subscriber           = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);
        $this->loginUser($subscriber);
        $tokenId   = 'update-campaign';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        $newDate = $faker->dateTimeBetween('-1 day', '-1 day');
        $this->client->request(
            'POST',
            '/passage34/modifier/campagne/'.$campaign->getId(),
            [
                'campaign-sending-date' => $newDate->format('Y-m-d'),
                'token'                 => $csrfToken,
            ]
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-primary > p', 'Formulaire invalide.');
    }

    /**
     * @test
     */
    public function it_removes_campaign(): void
    {
        // Arrange
        $faker        = Factory::create();
        $campaignName = $faker->word;
        $container    = $this->client->getContainer();
        /**
         * @var Connection $connection
         */
        $connection = $container->get(Connection::class);
        (new CampaignBuilder($connection))
            ->withName($campaignName)
            ->insert();

        // Act
        /**
         * @var CampaignRepository $campaignRepository
         */
        $campaignRepository = $container->get(CampaignRepository::class);
        $campaign           = $campaignRepository->findOneBy([
            'name' => $campaignName,
        ]);

        /**
         * @var SubscriberRepository $subscriberRepository
         */
        $subscriberRepository = $container->get(SubscriberRepository::class);
        $subscriber           = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);
        $this->loginUser($subscriber);

        $tokenId   = 'remove-campaign';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        $this->client->request(
            'POST',
            '/passage34/supprimer/campagne',
            [
                'campaign_id' => $campaign->getId(),
                'token'       => $csrfToken,
            ]
        );
        $this->client->followRedirect();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success > p', "La campagne {$campaign->getName()} a bien été supprimée 🎉");
        $campaign = $campaignRepository->findOneBy([
            'name' => $campaignName,
        ]);
        $this->assertEquals(null, $campaign);
    }

    /**
     * @test
     */
    public function it_sends_test_campaign(): void
    {
        // Arrange
        $faker                = Factory::create();
        $container            = $this->client->getContainer();
        $sendInBlueApiService = $this->createMock(SendInBlueApiService::class);
        $campaignName         = $faker->word;
        $sendInBlueApiService
            ->method('getTemplate')
            ->willReturn(
                new GetSmtpTemplateOverview([
                'id'   => $faker->randomNumber(),
                'name' => $campaignName,
            ])
            );
        $sendInBlueApiService
            ->method('sendTransactionalEmail')
            ->willReturn(true);
        $container->set(SendInBlueApiService::class, $sendInBlueApiService);

        /**
         * @var Connection $connection
         */
        $connection = $container->get(Connection::class);
        (new CampaignBuilder($connection))
            ->withName($campaignName)
            ->insert();

        // Act
        /**
         * @var CampaignRepository $campaignRepository
         */
        $campaignRepository = $container->get(CampaignRepository::class);
        $campaign           = $campaignRepository->findOneBy([
            'name' => $campaignName,
        ]);

        /**
         * @var SubscriberRepository $subscriberRepository
         */
        $subscriberRepository = $container->get(SubscriberRepository::class);
        $subscriber           = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);
        $this->loginUser($subscriber);

        $tokenId   = 'test-campaign';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        $this->client->request(
            'POST',
            '/passage34/test/campagne',
            [
                'campaign_id' => $campaign->getId(),
                'token'       => $csrfToken,
            ]
        );
        $this->client->followRedirect();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success > p', "La campagne {$campaign->getName()} a bien été envoyée à {$subscriber->getEmail()} 🎉");
    }
}
