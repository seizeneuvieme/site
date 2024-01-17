<?php

namespace App\Service;

use App\DTO\SubscriberCreate;
use App\Entity\Platform;
use App\Entity\Subscriber;
use App\Repository\SubscriberRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SubscriberService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SubscriberRepository $subscriberRepository
    ) {
    }

    public function doesSubscriberAlreadyExist(SubscriberCreate $subscriberCreate): bool
    {
        $subscriber = $this->subscriberRepository->findOneBy([
            'email' => $subscriberCreate->email,
        ]);

        return $subscriber !== null;
    }

    public function createSubscriberFromDTO(SubscriberCreate $subscriberCreate): Subscriber
    {
        $subscriber = new Subscriber();
        $subscriber->setEmail($subscriberCreate->email);
        $hashedPassword = $this->passwordHasher->hashPassword($subscriber, $subscriberCreate->password);
        $subscriber->setPassword($hashedPassword);
        $subscriber->setFirstname($subscriberCreate->firstname);
        $subscriber->setCity($subscriberCreate->city);
        $subscriber->setDepartmentNumber($subscriberCreate->departmentNumber);
        $subscriber->setDepartmentName($subscriberCreate->departmentName);
        $subscriber->setRegion($subscriberCreate->region);

        foreach ($subscriberCreate->streamingPlatforms as $streamingPlatform) {
            $platform = new Platform();
            $platform->setName($streamingPlatform);
            $subscriber->addPlatform($platform);
        }

        return $subscriber;
    }
}
