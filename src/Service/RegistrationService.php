<?php

namespace App\Service;

use App\DTO\Subscription;
use App\Entity\Child;
use App\Entity\Platform;
use App\Entity\Subscriber;
use App\Repository\SubscriberRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SubscriptionService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SubscriberRepository        $subscriberRepository
    ){}

    public function processCityDetails(Subscription $subscription): void
    {
        if (null !== $subscription->cityDetails) {
            $cityDetails = explode(',', $subscription->cityDetails);
            if (count($cityDetails) === 3) {
                $subscription->departmentNumber = trim($cityDetails[0]);
                $subscription->departmentName = trim($cityDetails[1]);
                $subscription->region = trim($cityDetails[2]);
            }
        }
    }

    public function doesUserAlreadyExist(Subscription $subscription): bool
    {
        $subscriber = $this->subscriberRepository->findOneBy([
            'email' => $subscription->email

        ]);

        return $subscriber !== null;
    }

    public function createSubscriberFromDTO(Subscription $subscription): Subscriber
    {
        $subscriber = new Subscriber();
        $subscriber->setEmail($subscription->email);
        $hashedPassword = $this->passwordHasher->hashPassword($subscriber, $subscription->password);
        $subscriber->setPassword($hashedPassword);
        $subscriber->setFirstname($subscription->firstname);
        $subscriber->setCity($subscription->city);
        $subscriber->setDepartmentNumber($subscription->departmentNumber);
        $subscriber->setDepartmentName($subscription->departmentName);
        $subscriber->setRegion($subscription->region);

        $child = new Child();
        $child->setFirstname($subscription->childFirstname);
        $child->setBirthDate($subscription->childBirthDate);
        $subscriber->addChild($child);

        foreach ($subscription->streamingPlatforms as $streamingPlatform) {
            $platform = new Platform();
            $platform->setName($streamingPlatform);
            $subscriber->addPlatform($platform);
        }

        return $subscriber;
    }
}