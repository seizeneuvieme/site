<?php

namespace App\Service;

use Exception;
use GuzzleHttp\Client;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\GetSmtpTemplateOverview;
use SendinBlue\Client\Model\SendSmtpEmail;

class SendInBlueApiService
{
    public const ACTIVE_ACCOUNT_TEMPLATE_ID = 4;
    public const RESET_PASSWORD_TEMPLATE_ID = 5;

    public function __construct(
        private readonly string $apiKey
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
            return $apiInstance->getSmtpTemplate($templateId);
        } catch (Exception $e) {
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

        $sendSmtpEmail                = new SendSmtpEmail();
        $sendSmtpEmail['subject']     = $template->getSubject();
        $sendSmtpEmail['htmlContent'] = $template->getHtmlContent();
        $sendSmtpEmail['sender']      = [
            'name'  => $template->getSender()->getName(),
            'email' => $template->getSender()->getEmail(),
        ];
        $sendSmtpEmail['to']         = [$to];
        $sendSmtpEmail['templateId'] = $template->getId();
        $sendSmtpEmail['tags']       = [$template->getTag()];
        $sendSmtpEmail['params']     = $params;

        try {
            $apiInstance->sendTransacEmail($sendSmtpEmail);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
