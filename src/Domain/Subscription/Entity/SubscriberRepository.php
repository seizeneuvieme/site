<?php

namespace Domain\Subscription\Entity;

use Domain\Subscription\Entity\Subscriber;

interface SubscriberRepository
{
    public function findOneBy(array $params): ?Subscriber;
}