<?php

namespace App\Service;

use App\Entity\Subscriber;
use GuzzleHttp\Client;
use SendinBlue\Client\Api\ContactsApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\CreateContact;

class SendInBlueApiService
{
    public function __construct(
        private string $apiKey
    ){}

    public function createContact(Subscriber $subscriber)
    {
        $configuration = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
        $apiInstance = new ContactsApi(
            new Client(),
            $configuration
        );

        $createContact = new CreateContact(); // Values to create a contact
        $createContact['email'] = $subscriber->getEmail();

        $attributes = [
            'firstname' => $subscriber->getFirstname(),
            'city' => $subscriber->getCity(),
            'departmentNumber' => $subscriber->getDepartmentNumber(),
            'departmentName' => $subscriber->getDepartmentName(),
            'region' => $subscriber->getRegion(),
        ];

        foreach ($subscriber->getChilds() as $child) {
            $attributes['childs'][] = [
                'firstname' => $child->getFirstname(),
                'birthDate' => $child->getBirthDate()
            ];
        }
        foreach ($subscriber->getPlatforms() as $platform) {
            $attributes['platforms'][] = [
                $platform->getName()
            ];
        }

        $createContact['attributes'] = $attributes;
        $createContact['listIds'] = [3];

        return $apiInstance->createContact($createContact);
    }
}