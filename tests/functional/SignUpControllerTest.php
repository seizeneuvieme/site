<?php

namespace App\Tests\functional;

use App\Entity\Platform;
use App\Repository\SubscriberRepository;
use App\Service\SendInBlueApiService;
use DateTime;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use SendinBlue\Client\Model\GetSmtpTemplateOverview;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

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
        /**
         * @var PasswordAuthenticatedUserInterface $subscriber
         */
        $this->assertTrue($passwordHasher->isPasswordValid($subscriber, $password), $subscriber->getPassword() ?? '');
    }
}
