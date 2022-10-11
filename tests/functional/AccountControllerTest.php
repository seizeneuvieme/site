<?php

namespace App\Tests\functional;

use App\Entity\Platform;
use App\Repository\SubscriberRepository;
use App\Service\SendInBlueApiService;
use App\Tests\builder\database\SubscriberBuilder;
use DateTime;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use SendinBlue\Client\Model\GetSmtpTemplateOverview;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

class AccountControllerTest extends AbstractWebTestCase
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
            ->withIsVerified(false)
            ->withPassword('ouvre toi STP')
            ->insert();
    }

    /**
     * @test
     */
    public function it_gets_account(): void
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
        $this->client->request('GET', '/mon-compte');
        $this->client->followRedirect();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', "👋 {$subscriber?->getFirstname()}");
    }

    /**
     * @test
     */
    public function it_redirects_to_sign_in_if_not_logged(): void
    {
        // Act
        $this->client->request('GET', '/mon-compte');
        $this->client->followRedirect();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Connexion');
    }

    /**
     * @test
     */
    public function it_sends_activation_code(): void
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

        $sendInBlueApiService = $this->createMock(SendInBlueApiService::class);
        $sendInBlueApiService->expects(self::once())
            ->method('getTemplate')
            ->willReturn(new GetSmtpTemplateOverview())
        ;
        $sendInBlueApiService->expects(self::once())
            ->method('sendTransactionalEmail')
        ;
        $container->set(SendInBlueApiService::class, $sendInBlueApiService);

        // Act
        $this->client->request('GET', '/mon-compte/renvoi-code-activation');
        $this->client->followRedirect();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.send-new-activation-code > p', "Un nouveau code d'activatation a été envoyé sur ton adresse {$subscriber->getEmail()} 🎉");
    }

    /**
     * @test
     */
    public function it_updates_email(): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'update-email';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/modifier/email',
            [
                'email' => 'doc@doc.com',
                'token' => $csrfToken,
            ]
        );

        $subscriber = $subscriberRepository->findOneBy([
            'id' => $subscriber->getId(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success > p', 'Ton adresse email a bien été modifiée 🎉');
        $this->assertEquals('doc@doc.com', $subscriber->getEmail());
    }

    /**
     * @test
     *
     * @dataProvider invalidEmailProvider
     */
    public function it_does_not_update_email_if_invalid(string $email, string $seletor, string $message): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'update-email';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/modifier/email',
            [
                'email' => $email,
                'token' => $csrfToken,
            ]
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains($seletor, $message);
    }

    public function invalidEmailProvider(): array
    {
        $faker = Factory::create();

        return [
            'invalid email' => [
                $faker->word,
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
            'invalid already exist' => [
                'marty@mcfly.com',
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_updates_password(): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'update-password';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        $password = 'a brand new password';
        /**
         * @var UserPasswordHasherInterface $passwordHasher
         */
        $passwordHasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/modifier/mot-de-passe',
            [
                'password'         => $password,
                'confirm-password' => $password,
                'token'            => $csrfToken,
            ]
        );

        $subscriber = $subscriberRepository->findOneBy([
            'id' => $subscriber->getId(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success > p', 'Ton mot de passe a bien été modifiée 🎉');
        $this->assertTrue($passwordHasher->isPasswordValid($subscriber, $password));
    }

    /**
     * @test
     *
     * @dataProvider invalidPasswordProvider
     */
    public function it_does_not_update_password_if_invalid(string $password, string $confirmPassword, string $seletor, string $message): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'update-password';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        // Act
        $crawler = $this->client->request(
            'POST',
            '/mon-compte/modifier/mot-de-passe',
            [
                'password'         => $password,
                'confirm-password' => $confirmPassword,
                'token'            => $csrfToken,
            ]
        );
        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains($seletor, $message);
    }

    public function invalidPasswordProvider(): array
    {
        $faker = Factory::create();

        return [
            'password too short' => [
                'abc',
                'abc',
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
            "password and confirm password don't match" => [
                $faker->password,
                'a brand new password that does not match',
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_updates_user_infos(): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'update-user-infos';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        $faker            = Factory::create();
        $firstname        = $faker->firstName;
        $city             = $faker->city;
        $departmentNumber = $faker->numberBetween(10, 95);
        $departmentName   = $faker->word;
        $region           = $faker->word;
        $cityDetails      = $departmentNumber.', '.$departmentName.', '.$region;

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/modifier/coordonnees',
            [
                'firstname'    => $firstname,
                'city'         => $city,
                'city-details' => $cityDetails,
                'token'        => $csrfToken,
            ]
        );

        $subscriber = $subscriberRepository->findOneBy([
            'id' => $subscriber->getId(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success > p', 'Tes coordonnées ont bien été modifiées 🎉');
        $this->assertEquals($firstname, $subscriber->getFirstname());
        $this->assertEquals($city, $subscriber->getCity());
        $this->assertEquals($departmentNumber, $subscriber->getDepartmentNumber());
        $this->assertEquals($departmentName, $subscriber->getDepartmentName());
        $this->assertEquals($region, $subscriber->getRegion());
    }

    /**
     * @test
     *
     * @dataProvider invalidUserInfosProvider
     */
    public function it_does_not_update_user_infos_if_invalid(array $invalidFields, string $seletor, string $message): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'update-user-infos';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/modifier/coordonnees',
            array_merge(
                $invalidFields,
                [
                    'token' => $csrfToken,
                ]
            )
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains($seletor, $message);
    }

    public function invalidUserInfosProvider(): array
    {
        $faker = Factory::create();

        return [
            'firstname too short' => [
                [
                    'firstname'    => 'ab',
                    'city'         => $faker->city,
                    'city-details' => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                ],
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
            'firstname too long' => [
                [
                    'firstname'    => 'azertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbn',
                    'city'         => $faker->city,
                    'city-details' => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                ],
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
            'blank city' => [
                [
                    'firstname'    => $faker->firstName,
                    'city-details' => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                ],
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
            'blank city details' => [
                [
                    'firstname' => $faker->firstName,
                    'city'      => $faker->city,
                ],
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_updates_platforms(): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'update-platforms';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/modifier/plateformes',
            [
                'streaming' => [Platform::DISNEY],
                'token'     => $csrfToken,
            ]
        );

        $subscriber = $subscriberRepository->findOneBy([
            'id' => $subscriber->getId(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success > p', 'Tes plateformes de streaming payantes ont bien été modifiées 🎉');
        $this->assertEquals(1, $subscriber->getPlatforms()->count());
    }

    /**
     * @test
     *
     * @dataProvider invalidPlatformsProvider
     */
    public function it_does_not_update_platforms_if_invalid(array $invalidFields, string $seletor, string $message): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'update-platforms';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/modifier/plateformes',
            array_merge(
                $invalidFields,
                [
                    'token' => $csrfToken,
                ]
            )
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains($seletor, $message);
    }

    public function invalidPlatformsProvider(): array
    {
        return [
            'invalid platform' => [
                [
                    'streaming' => ['Benshi'],
                ],
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_adds_child(): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'add-child';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);
        $faker          = Factory::create();
        $childFirstname = $faker->firstName;
        $childBirthdate = '2018-09-10';

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/ajouter/enfant',
            [
                'child-firstname'  => $childFirstname,
                'child-birth-date' => $childBirthdate,
                'token'            => $csrfToken,
            ]
        );

        $subscriber = $subscriberRepository->findOneBy([
            'id' => $subscriber->getId(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success > p', "✅ C'est noté ! Tu recevras désormais aussi des recommandations de films pour $childFirstname");
        $this->assertEquals(1, $subscriber->getChilds()->count());
    }

    /**
     * @test
     *
     * @dataProvider invalidChildsProvider
     */
    public function it_does_not_add_child_if_invalid(array $invalidFields, string $seletor, string $message): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'add-child';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/ajouter/enfant',
            array_merge(
                $invalidFields,
                [
                    'token' => $csrfToken,
                ]
            )
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains($seletor, $message);
    }

    public function invalidChildsProvider(): array
    {
        $faker = Factory::create();

        return [
            'child firstname too short' => [
                [
                    'child-firstname'  => 'ab',
                    'child-birth-date' => '2018-09-10',
                ],
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
            'child firstname too long' => [
                [
                    'child-firstname'  => 'text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125.text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125.',
                    'child-birth-date' => '2018-09-10',
                ],
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
            'child too young' => [
                [
                    'child-firstname'  => $faker->firstName,
                    'child-birth-date' => (new DateTime('NOW'))->format('Y-m-d'),
                ],
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
            'child too old' => [
                [
                    'child-firstname'  => $faker->firstName,
                    'child-birth-date' => '2000-09-10',
                ],
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_removes_child(): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'add-child';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);
        $faker          = Factory::create();
        $childFirstname = $faker->firstName;
        $childBirthdate = '2018-09-10';

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/ajouter/enfant',
            [
                'child-firstname'  => $childFirstname,
                'child-birth-date' => $childBirthdate,
                'token'            => $csrfToken,
            ]
        );

        $this->client->request(
            'POST',
            '/mon-compte/ajouter/enfant',
            [
                'child-firstname'  => $childFirstname,
                'child-birth-date' => $childBirthdate,
                'token'            => $csrfToken,
            ]
        );

        $subscriber = $subscriberRepository->findOneBy([
            'id' => $subscriber->getId(),
        ]);

        $tokenId   = 'remove-child';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        $firstChild = $subscriber->getChilds()->first();
        $this->client->request(
            'POST',
            '/mon-compte/supprimer/enfant',
            [
                'child-id' => $firstChild->getId(),
                'token'    => $csrfToken,
            ]
        );
        $this->client->followRedirect();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success > p', "✅ C'est noté ! Tu recevras plus de recommandations de films pour {$firstChild->getFirstname()}");
    }

    /**
     * @test
     */
    public function it_does_not_remove_child_if_subscriber_only_have_one(): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'add-child';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);
        $faker          = Factory::create();
        $childFirstname = $faker->firstName;
        $childBirthdate = '2018-09-10';

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/ajouter/enfant',
            [
                'child-firstname'  => $childFirstname,
                'child-birth-date' => $childBirthdate,
                'token'            => $csrfToken,
            ]
        );

        $subscriber = $subscriberRepository->findOneBy([
            'id' => $subscriber->getId(),
        ]);

        $tokenId   = 'remove-child';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        $firstChild = $subscriber->getChilds()->first();
        $this->client->request(
            'POST',
            '/mon-compte/supprimer/enfant',
            [
                'child-id' => $firstChild->getId(),
                'token'    => $csrfToken,
            ]
        );
        $this->client->followRedirect();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('div.alert-success > p', "✅ C'est noté ! Tu recevras plus de recommandations de films pour {$firstChild->getFirstname()}");
    }

    /**
     * @test
     */
    public function it_removes_account(): void
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

        $this->loginUser($subscriber);

        $tokenId   = 'update-password';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        // Act
        $this->client->request(
            'POST',
            '/mon-compte/modifier/mot-de-passe',
            [
                'password'         => 'ouvre toi STP',
                'confirm-password' => 'ouvre toi STP',
                'token'            => $csrfToken,
            ]
        );

        // Reboot kernel manually
        $this->client->getKernel()->shutdown();
        $this->client->getKernel()->boot();
        // Prevent client from rebooting the kernel
        $this->client->disableReboot();

        $sendInBlueApiService = $this->createMock(SendInBlueApiService::class);
        $sendInBlueApiService->expects(self::once())
            ->method('getTemplate')
            ->willReturn(new GetSmtpTemplateOverview())
        ;
        $sendInBlueApiService->expects(self::once())
            ->method('sendTransactionalEmail')
        ;
        $container->set(SendInBlueApiService::class, $sendInBlueApiService);

        $tokenId   = 'remove-account';
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);
        $this->client->request(
            'POST',
            '/mon-compte/supprimer/compte',
            [
                'password' => 'ouvre toi STP',
                'token'    => $csrfToken,
            ]
        );

        // Assert
        $this->assertSelectorTextContains('title', 'Redirecting to /deconnexion');
        $subscriber = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);
        $this->assertEquals(null, $subscriber);
    }
}
