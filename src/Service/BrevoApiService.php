<?php

namespace App\Service;

use App\Entity\Subscriber;
use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\ApiException;
use Brevo\Client\Configuration;
use Brevo\Client\Model\CreateContact;
use Brevo\Client\Model\GetSmtpTemplateOverview;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailSender;
use Brevo\Client\Model\SendSmtpEmailTo;
use Brevo\Client\Model\UpdateContact;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class BrevoApiService
{
    public const NEWSLETTER_LIST_ID         = 3;
    public const ACTIVE_ACCOUNT_TEMPLATE_ID = 4;
    public const RESET_PASSWORD_TEMPLATE_ID = 5;
    public const CONFIRM_CAMPAIGN_SENT      = 6;
    public const CONFIRM_ACCOUNT_REMOVED    = 7;
    public const CONFIRM_CAMPAIGN_TO        = [
        'name'  => 'Fanny',
        'email' => 'fanny@seize9eme.fr',
    ];

    public function __construct(
        private readonly string $apiKey,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getTemplate(int $templateId): ?GetSmtpTemplateOverview
    {
        $configuration = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
        $apiInstance   = new TransactionalEmailsApi(
            new Client(),
            $configuration
        );

        try {
            $this->logger->info(
                'GET_TEMPLATE',
                [
                    'templateId' => $templateId,
                ]
            );

            return $apiInstance->getSmtpTemplate($templateId);
        } catch (\Exception $e) {
            $this->logger->error(
                'GET_TEMPLATE_ERROR',
                [
                    'exception'  => $e,
                    'templateId' => $templateId,
                ]
            );

            return null;
        }
    }

    public function sendTransactionalEmail(GetSmtpTemplateOverview $template, array $to, array $params = []): bool
    {
        $configuration = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
        $apiInstance   = new TransactionalEmailsApi(
            new Client(),
            $configuration
        );

        $sendSmtpEmail = new SendSmtpEmail();
        $sendSmtpEmail->setSubject($template->getSubject());
        $sendSmtpEmail->setHtmlContent($template->getHtmlContent());
        $sendSmtpEmail->setSender(new SendSmtpEmailSender([
            'name'  => $template->getSender()->getName(),
            'email' => $template->getSender()->getEmail(),
        ]));
        $sendSmtpEmail->setTo([new SendSmtpEmailTo($to)]);
        $sendSmtpEmail->setTemplateId($template->getId());
        $sendSmtpEmail->setTags([$template->getTag()]);
        $sendSmtpEmail->setParams((object) $params);

        try {
            $apiInstance->sendTransacEmail($sendSmtpEmail);
            $this->logger->info(
                'EMAIL_SENT',
                [
                    'to' => $to,
                ]
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                'SEND_EMAIL_ERROR',
                [
                    'to'        => $to,
                    'exception' => $e,
                ]
            );

            return false;
        }
    }

    /**
     * @throws ApiException
     */
    public function createUpdateContact(Subscriber $subscriber): ?int
    {
        $configuration = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
        $apiInstance   = new ContactsApi(
            new Client(),
            $configuration
        );
        $contact = new CreateContact();
        $contact->setEmail($subscriber->getEmail());

        $contact->setListIds([self::NEWSLETTER_LIST_ID]);
        $contact->setUpdateEnabled(true);

        $attributes = [
            'PRENOM'  => $subscriber->getFirstname(),
            'TNT'     => false,
            'NETFLIX' => false,
            'PRIME'   => false,
            'DISNEY'  => false,
            'CANAL'   => false,
        ];
        foreach ($subscriber->getPlatforms() as $platform) {
            $attributes[strtoupper((string) $platform->getName())] = true;
        }
        $contact->setAttributes((object) $attributes);

        $contact = $apiInstance->createContact($contact);

        return $contact !== null ? $contact->getId() : null;
    }

    /**
     * @throws ApiException
     */
    public function updateContactEmail(Subscriber $subscriber): void
    {
        $configuration = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
        $apiInstance   = new ContactsApi(
            new Client(),
            $configuration
        );
        $contact    = new UpdateContact();
        $attributes = [
            'EMAIL' => $subscriber->getEmail(),
        ];
        $contact->setAttributes((object) $attributes);
        $apiInstance->updateContact((string) $subscriber->getBrevoContactId(), $contact);
    }

    /**
     * @throws ApiException
     */
    public function deleteContact(?int $id): void
    {
        $configuration = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
        $apiInstance   = new ContactsApi(
            new Client(),
            $configuration
        );

        $apiInstance->deleteContact((string) $id);
    }
}
