<?php

namespace Domain\Subscription\Usecase\SignUp;
 
use Domain\Subscription\Entity\Subscriber;
use Seat\Shared\Error\Notification;

class SignUpResponse
{
    private Subscriber $subscriber;
    
    private Notification $note;
    
    public function getSubscriber(): Subscriber
    {
        return $this->subscriber;
    }
    
    public function setSubscriber(Subscriber $subscriber): void
    {
        $this->subscriber = $subscriber;
    }

      public function addError(string $fieldName, string $error)
    {
        $this->note->addError($fieldName, $error);
    }

    public function notification(): Notification
    {
        return $this->note;
    }
}