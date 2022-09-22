<?php

namespace App\Service;

use App\DTO\Registration;
use App\Entity\Child;
use App\Entity\Platform;
use App\Entity\Subscriber;
use App\Repository\SubscriberRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SubscriberRepository        $subscriberRepository
    ){}

    public function processCityDetails(Registration $registration): void
    {
        if (null !== $registration->cityDetails) {
            $cityDetails = explode(',', $registration->cityDetails);
            if (count($cityDetails) === 3) {
                $registration->departmentNumber = trim($cityDetails[0]);
                $registration->departmentName = trim($cityDetails[1]);
                $registration->region = trim($cityDetails[2]);
            }
        }
    }

    public function doesUserAlreadyExist(Registration $subscription): bool
    {
        $subscriber = $this->subscriberRepository->findOneBy([
            'email' => $subscription->email

        ]);

        return $subscriber !== null;
    }

    public function createSubscriberFromDTO(Registration $registration): Subscriber
    {
        $subscriber = new Subscriber();
        $subscriber->setEmail($registration->email);
        $hashedPassword = $this->passwordHasher->hashPassword($subscriber, $registration->password);
        $subscriber->setPassword($hashedPassword);
        $subscriber->setFirstname($registration->firstname);
        $subscriber->setCity($registration->city);
        $subscriber->setDepartmentNumber($registration->departmentNumber);
        $subscriber->setDepartmentName($registration->departmentName);
        $subscriber->setRegion($registration->region);

        $child = new Child();
        $child->setFirstname($registration->childFirstname);
        $child->setBirthDate($registration->childBirthDate);
        $subscriber->addChild($child);

        foreach ($registration->streamingPlatforms as $streamingPlatform) {
            $platform = new Platform();
            $platform->setName($streamingPlatform);
            $subscriber->addPlatform($platform);
        }

        return $subscriber;
    }
}