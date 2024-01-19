<?php

namespace App\Tests\unit;

use App\DTO\SubscriberCreate;
use App\Entity\Platform;
use App\Entity\Subscriber;
use App\Repository\SubscriberRepository;
use App\Service\SubscriberService;
use Faker\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SubscriberServiceTest extends TestCase
{
    private UserPasswordHasherInterface|MockObject $userPasswordHasher;
    private SubscriberRepository|MockObject $subscriberRepository;
    private SubscriberService $subscriberService;
    private SubscriberCreate $subscriberCreate;
    private string $fakeHashedPassword;
    private Subscriber $subscriber;

    public function setUp(): void
    {
        $this->userPasswordHasher = $this->getMockBuilder(UserPasswordHasherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriberRepository = $this->getMockBuilder(SubscriberRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriberService = new SubscriberService($this->userPasswordHasher, $this->subscriberRepository);

        $faker                                      = Factory::create();
        $this->subscriberCreate                     = new SubscriberCreate();
        $this->subscriberCreate->email              = $faker->email;
        $password                                   = $faker->password;
        $this->subscriberCreate->password           = $password;
        $this->subscriberCreate->confirmPassword    = $password;
        $this->subscriberCreate->firstname          = $faker->firstName;
        $this->subscriberCreate->streamingPlatforms = [Platform::NETFLIX];
    }

    /**
     * @test
     */
    public function it_returns_false_if_subscriber_does_not_exist(): void
    {
        // Arrange
        $this->subscriberRepository
            ->method('findOneBy')
            ->willReturn(null);

        // Act
        $result = $this->subscriberService->doesSubscriberAlreadyExist($this->subscriberCreate);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_returns_true_if_subscriber_does_not_exist(): void
    {
        // Arrange
        $this->subscriberRepository
            ->method('findOneBy')
            ->willReturn(new Subscriber());

        // Act
        $result = $this->subscriberService->doesSubscriberAlreadyExist($this->subscriberCreate);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_creates_subscriber_from_dto(): void
    {
        // Arrange
        $faker                    = Factory::create();
        $this->fakeHashedPassword = $faker->password;

        $this->userPasswordHasher
            ->method('hashPassword')
            ->willReturn($this->fakeHashedPassword);

        // Act
        $this->subscriber = $this->subscriberService->createSubscriberFromDTO($this->subscriberCreate);

        // Assert
        $this->assertCreatedSubscriber();
    }

    private function assertCreatedSubscriber(): void
    {
        $this->assertEquals($this->subscriber->getEmail(), $this->subscriberCreate->email);
        $this->assertEquals($this->subscriber->getPassword(), $this->fakeHashedPassword);
        $this->assertEquals($this->subscriber->getFirstname(), $this->subscriberCreate->firstname);
        $this->assertEquals(1, $this->subscriber->getPlatforms()->count());
        $this->assertEquals($this->subscriber->getPlatforms()->toArray()[0]->getName(), $this->subscriberCreate->streamingPlatforms[0]);
    }
}
