<?php

namespace Domain\Subscription\UseCase\SignUp;

use Domain\Subscription\Entity\Subscriber;
use Domain\Subscription\Entity\SubscriberRepository;
use Domain\Shared\Helper\Logger;

class SignUpUseCase
{
    public function __construct(
        private SubscriberRepository $subscriberRepository,
        private Logger $logger,
    ){}
    
    public function execute(SignUpRequest $signUpRequest): SignUpResponse {
        
        $response = new SignUpResponse();
        
        $subscriber = $signUpRequest->getSubscriber();
        $isValid = $this->validateSubscriber($subscriber, $response);
        
        try {
            $subscriber->setFirstname($signUpRequest->firstname);
            $subscriber->setEmail($signUpRequest->email);
            $subscriber->setPassword($signUpRequest->firstname);
            $subscriber->setStreamingPlatforms($signUpRequest->firstname);
        } catch (\Exception $exception) {
            
        }
        
        
    }

    private function validateSubscriber(Subscriber $subscriber, SignUpResponse $response): bool
    {
        if (!filter_var($subscriber->email, FILTER_VALIDATE_EMAIL)) {
            $response->addError('email', 'email is invalid');
            $this->logger->error('email is invalid', ['email' => $subscriber->email]);
            return false;
        }
        
        $emailAlreadyInUse = $this->subscriberRepository->findOneBy(['email' => $subscriber->email]) === null;
        if ($emailAlreadyInUse) {
            $response->addError('email', 'already in use');
            $this->logger->error('email is already in use', ['email' => $subscriber->email]);
            return false;
        }
        
        if (strlen($subscriber->firstname) > 255) {
            $response->addError('firstname', 'too long');
            $this->logger->error('firstname is too long', ['email' => $subscriber->email, 'firstname' => $subscriber->firstname]);
            return false;
        }
        
        if (strlen($subscriber->firstname) > 255) {
            $response->addError('firstname', 'too long');
            $this->logger->error('firstname is too long', ['email' => $subscriber->email, 'firstname' => $subscriber->firstname]);
            return false;
        }
        
        if (strlen($subscriber->password) < 8 || strlen($subscriber->password) > 255) {
            $response->addError('password', '');
            $this->logger->error('', ['email' => $subscriber->email]);
            return false;
        }
        
        
        return true;
    }
}
