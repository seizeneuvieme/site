<?php

namespace App\Tests\functional;

use App\Entity\Platform;
use App\Entity\Subscriber;
use App\Repository\SubscriberRepository;
use App\Service\SendInBlueApiService;
use App\Tests\builder\database\SubscriberBuilder;
use DateTime;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use SendinBlue\Client\Model\GetSmtpTemplateOverview;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class SignUpControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $container    = static::getContainer();
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
            ->insert();
    }

    /**
     * @test
     */
    public function it_gets_sign_up(): void
    {
        // Act
        $this->client->request('GET', '/inscription');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', "S'abonner à la newsletter Le Réhausseur");
    }

    /**
     * @test
     */
    public function it_signs_up_new_subscriber(): void
    {
        // Arrange
        $faker              = Factory::create();
        $email              = $faker->email;
        $password           = $faker->password;
        $firstname          = $faker->firstName;
        $city               = $faker->city;
        $departmentNumber   = $faker->numberBetween(10, 95);
        $departmentName     = $faker->word;
        $region             = $faker->word;
        $cityDetails        = "$departmentNumber, $departmentName, $region";
        $childFirstname     = $faker->firstName;
        $childBirthdate     = '2018-09-10';
        $streamingPlatforms = [Platform::DISNEY];

        $sendInBlueApiService = $this->createMock(SendInBlueApiService::class);

        $sendInBlueApiService->expects(self::once())
            ->method('getTemplate')
            ->willReturn(new GetSmtpTemplateOverview())
        ;
        $sendInBlueApiService->expects(self::once())
            ->method('sendTransactionalEmail')
        ;

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')
            ->willReturn($password);

        $container = static::getContainer();
        $container->set(SendInBlueApiService::class, $sendInBlueApiService);
        /**
         * @var SubscriberRepository $subscriberRepository
         */
        $subscriberRepository = $container->get(SubscriberRepository::class);
        /**
         * @var UserPasswordHasherInterface
         */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Act
        $this->client->request(
            'POST',
            '/inscription',
            [
                'email'            => $email,
                'password'         => $password,
                'confirm-password' => $password,
                'firstname'        => $firstname,
                'city'             => $city,
                'city-details'     => $cityDetails,
                'child-firstname'  => $childFirstname,
                'child-birth-date' => $childBirthdate,
                'streaming'        => $streamingPlatforms,
            ]
        );
        $this->client->followRedirect();
        $subscriber = $subscriberRepository->findOneBy([
            'email' => $email,
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', "👋 {$subscriber?->getFirstname()}");

        $this->assertEquals($email, $subscriber?->getEmail());
        $this->assertEquals($firstname, $subscriber?->getFirstname());
        $this->assertEquals($city, $subscriber?->getCity());
        $this->assertEquals($departmentNumber, $subscriber?->getDepartmentNumber());
        $this->assertEquals($departmentName, $subscriber?->getDepartmentName());
        $this->assertEquals($region, $subscriber?->getRegion());
        $this->assertEquals(1, $subscriber?->getChilds()->count());
        $this->assertEquals($childFirstname, $subscriber?->getChilds()->toArray()[0]->getFirstname());
        $this->assertEquals((new Datetime($childBirthdate))->format('d-m-Y'), $subscriber?->getChilds()->toArray()[0]->getBirthDate()->format('d-m-Y'));
        $this->assertEquals(1, $subscriber?->getPlatforms()->count());
        $this->assertEquals($streamingPlatforms[0], $subscriber?->getPlatforms()->toArray()[0]->getName());
        $this->assertEquals(false, $subscriber?->isVerified());
        /**
         * @var PasswordAuthenticatedUserInterface $subscriber
         */
        $this->assertTrue($passwordHasher->isPasswordValid($subscriber, $password), $subscriber->getPassword() ?? '');
    }

    /**
     * @test
     */
    public function it_verifies_user_email(): void
    {
        // Arrange
        $container = static::getContainer();
        /**
         * @var Connection $connection
         */
        $connection = $container->get(Connection::class);
        /**
         * @var SubscriberRepository $subscriberRepository
         */
        $subscriberRepository = $container->get(SubscriberRepository::class);
        $subscriber           = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);
        /**
         * @var VerifyEmailHelperInterface $verifyEmailHelper
         */
        $verifyEmailHelper   = $container->get(VerifyEmailHelperInterface::class);
        $signatureComponents = $verifyEmailHelper->generateSignature(
            'app_verify_email',
            "{$subscriber?->getId()}",
            "{$subscriber?->getEmail()}",
            ['id' => $subscriber?->getId()]
        );

        // Act
        /**
         * @var UserInterface $subscriber
         */
        $this->client->loginUser($subscriber);
        $this->client->request(
            'GET',
            $signatureComponents->getSignedUrl()
        );
        $this->client->followRedirect();

        /**
         * @var Subscriber $subscriber
         */
        $subscriber = $subscriberRepository->findOneBy([
            'email' => 'marty@mcfly.com',
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.account-activated', 'Ton compte a bien été activé 🎉');
        $this->assertEquals(true, $subscriber->isVerified());
    }

    /**
     * @test
     *
     * @dataProvider invalidFieldsProvider
     */
    public function it_gets_error_if_sign_up_form_has_errors(array $invalidFields, int $expectedStatusCode, string $expectedMessage): void
    {
        // Arrange
        $sendInBlueApiService = $this->createMock(SendInBlueApiService::class);
        $container            = static::getContainer();
        $container->set(SendInBlueApiService::class, $sendInBlueApiService);

        // Act
        $this->client->request(
            'POST',
            '/inscription',
            $invalidFields
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.invalid-form', $expectedMessage);
    }

    public function invalidFieldsProvider(): array
    {
        $faker    = Factory::create();
        $password = $faker->password;

        return [
            'missing email' => [
                [
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => $faker->firstName,
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => $faker->firstName,
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'invalid email' => [
                [
                    'email'            => $faker->word,
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => $faker->firstName,
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => $faker->firstName,
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'email already used' => [
                [
                    'email'            => 'marty@mcfly.com',
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => $faker->firstName,
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => $faker->firstName,
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                "L'adresse email marty@mcfly.com est déjà abonnée au Réhausseur 👉 Je me connecte",
            ],
            'password too short' => [
                [
                    'email'            => $faker->email,
                    'password'         => 'abc',
                    'confirm-password' => 'abc',
                    'firstname'        => $faker->firstName,
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => $faker->firstName,
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'password and confirm password don\'t match' => [
                [
                    'email'            => $faker->email,
                    'password'         => $password,
                    'confirm-password' => 'abc',
                    'firstname'        => $faker->firstName,
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => $faker->firstName,
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'firstname too short' => [
                [
                    'email'            => $faker->email,
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => 'ab',
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => $faker->firstName,
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'firstname too long' => [
                [
                    'email'            => $faker->email,
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => 'text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125.',
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => $faker->firstName,
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'blank city' => [
                [
                    'email'            => $faker->email,
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => $faker->word,
                    'city'             => '',
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => $faker->firstName,
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'invalid city details' => [
                [
                    'email'            => $faker->email,
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => $faker->word,
                    'city'             => $faker->city,
                    'city-details'     => $faker->word,
                    'child-firstname'  => $faker->firstName,
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'child firstname too short' => [
                [
                    'email'            => $faker->email,
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => $faker->word,
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => 'ab',
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'child firstname too long' => [
                [
                    'email'            => $faker->email,
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => $faker->word,
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => 'text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125.text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125. text with length more than 125.',
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'child too young' => [
                [
                    'email'            => $faker->email,
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => $faker->word,
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => $faker->word,
                    'child-birth-date' => (new DateTime('NOW'))->format('Y-m-d'),
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'child too old' => [
                [
                    'email'            => $faker->email,
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => $faker->word,
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => $faker->word,
                    'child-birth-date' => '2000-09-10',
                    'streaming'        => [Platform::DISNEY],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
            'invalid platform' => [
                [
                    'email'            => $faker->email,
                    'password'         => $password,
                    'confirm-password' => $password,
                    'firstname'        => $faker->word,
                    'city'             => $faker->city,
                    'city-details'     => $faker->numberBetween(10, 95).', '.$faker->word.', '.$faker->word,
                    'child-firstname'  => $faker->word,
                    'child-birth-date' => '2018-09-10',
                    'streaming'        => ['Benshi'],
                ],
                Response::HTTP_OK,
                'Formulaire invalide',
            ],
        ];
    }
}