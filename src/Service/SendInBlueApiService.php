<?php

namespace App\Service;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\GetSmtpTemplateOverview;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailSender;
use Brevo\Client\Model\SendSmtpEmailTo;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class SendInBlueApiService
{
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
}
