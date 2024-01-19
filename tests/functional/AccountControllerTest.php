<?php

namespace App\Tests\functional;

use App\Entity\Platform;
use App\Repository\SubscriberRepository;
use App\Service\SendInBlueApiService;
use App\Tests\builder\database\SubscriberBuilder;
use Brevo\Client\Model\GetSmtpTemplateOverview;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
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
        $this->client->request('GET', '/account');
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
        $this->client->request('GET', '/account');
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
        $this->client->request('GET', '/account/send-activation-code');
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
            '/account/email/edit',
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
            '/account/email/edit',
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
            '/account/password/edit',
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
            '/account/password/edit',
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
    public function it_updates_user_data(): void
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

        $faker     = Factory::create();
        $firstname = $faker->firstName;

        // Act
        $this->client->request(
            'POST',
            '/account/data/edit',
            [
                'firstname' => $firstname,
                'token'     => $csrfToken,
            ]
        );

        $subscriber = $subscriberRepository->findOneBy([
            'id' => $subscriber->getId(),
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success > p', 'Tes coordonnées ont bien été modifiées 🎉');
        $this->assertEquals($firstname, $subscriber->getFirstname());
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
            '/account/data/edit',
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
                    'firstname' => 'ab',
                ],
                'div.alert-primary > p',
                'Formulaire invalide.',
            ],
            'firstname too long' => [
                [
                    'firstname' => 'azertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbnazertyuiopqsdfghjklmwxcvbn',
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
            '/account/platforms/edit',
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
        $this->assertSelectorTextContains('div.alert-success > p', 'Tes plateformes de contenu ont bien été modifiées 🎉');
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
            '/account/platforms/edit',
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
            '/account/password/edit',
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
            '/account/delete',
            [
                'password' => 'ouvre toi STP',
                'token'    => $csrfToken,
            ]
        );

        // Assert
        $this->assertSelectorTextContains('title', 'Redirecting to /logout');
        $subscriber = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);
        $this->assertEquals(null, $subscriber);
    }
}
