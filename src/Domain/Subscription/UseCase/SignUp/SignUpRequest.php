<?php

namespace Domain\Subscription\Usecase\SignUp;

use Domain\Subscription\Entity\Subscriber;

class SignUpRequest
{
    private Subscriber $subscriber;
    
    public function getSubscriber(): Subscriber
    {
        return $this->subscriber;
    }
    
    public static function create(array $signUpRequestData): self
    {
        $signUpRequest = new self();
        $subscriber = new Subscriber();
        $subscriber->setFirstname($signUpRequestData['firstname'] ?? null);
        $subscriber->setEmail($signUpRequestData['email'] ?? null);
        $subscriber->setPassword((($signUpRequestData['password'] ?? null) === ($signUpRequestData['confirmPassword'] ?? null)) ? $signUpRequestData['password'] : null);
        $subscriber->setStreamingPlatforms($signUpRequestData['streamingPlatforms'] ?? null);
        return $signUpRequest;
    }
}
