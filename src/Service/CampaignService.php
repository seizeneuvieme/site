<?php

namespace App\Service;

use App\DTO\CampaignCreate;
use App\Entity\Campaign;
use App\Entity\Platform;
use App\Entity\Subscriber;
use App\Repository\SubscriberRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CampaignService
{
    public function __construct(
        private readonly SubscriberRepository $subscriberRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SendInBlueApiService $sendInBlueApiService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createCampaignFromDTO(CampaignCreate $campaignCreate): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($campaignCreate->name);
        $campaign->setTemplateId($campaignCreate->templateId);
        $campaign->setSendingDate($campaignCreate->sendingDate);

        return $campaign;
    }

    /**
     * @throws Exception
     */
    public function processCampaign(Campaign $campaign): void
    {
        $template = $this->sendInBlueApiService->getTemplate($campaign->getTemplateId());

        if ($template === null) {
            throw new Exception("Template with id {$campaign->getTemplateId()} not found.");
        }

        $subscribers = $this->subscriberRepository->findBy([
            'isVerified' => true,
        ]);

        $numberEmailSent = 0;
        $numberOfErrors  = 0;

        foreach ($subscribers as $subscriber) {
            try {
                $params = $this->createParams($subscriber);

                if (
                    $params['TNT'] === false
                    && $params['NETFLIX'] === false
                    && $params['PRIME'] === false
                    && $params['DISNEY'] === false
                    && $params['CANAL'] === false
                ) {
                    continue;
                }

                $result = $this->sendInBlueApiService->sendTransactionalEmail(
                    $template,
                    [
                        'name'  => $subscriber->getFirstname(),
                        'email' => $subscriber->getEmail(),
                    ],
                    $params
                );

                $result === true ? $numberEmailSent++ : $numberOfErrors++;
            } catch (Exception $e) {
                $this->logger->error('EMAIL_SEND_ERROR', [
                    'campaignId'   => $campaign->getId(),
                    'campaignName' => $campaign->getName(),
                    'user'         => $subscriber->getId(),
                    'exception'    => $e,
                ]);
                continue;
            }
        }

        $campaign->setNumberSent($numberEmailSent);
        $campaign->setState(Campaign::SENT_STATE);
        $this->entityManager->flush();

        $template = $this->sendInBlueApiService->getTemplate(SendInBlueApiService::CONFIRM_CAMPAIGN_SENT);
        if ($template === null) {
            throw new Exception('Confirm campaign template id not found.');
        }
        $this->sendInBlueApiService->sendTransactionalEmail(
            $template,
            SendInBlueApiService::CONFIRM_CAMPAIGN_TO,
            [
                'CAMPAIGN_NAME'  => $campaign->getName(),
                'MAILS_SENT'     => $numberEmailSent,
                'MAILS_IN_ERROR' => $numberOfErrors,
            ]
        );
        $this->logger->info('CAMPAIGN_SENT', [
            'campaignId'   => $campaign->getId(),
            'campaignName' => $campaign->getName(),
            'emailSent'    => $numberEmailSent,
            'emailError'   => $numberOfErrors,
        ]);
    }

    public function createParams(Subscriber $subscriber): array
    {
        $params                      = [];
        $params['FIRSTNAME']         = $subscriber->getFirstname();
        $params['CITY']              = $subscriber->getCity();
        $params['DEPARTMENT_NAME']   = $subscriber->getDepartmentName();
        $params['DEPARTMENT_NUMBER'] = $subscriber->getDepartmentNumber();
        $params['REGION']            = $subscriber->getRegion();
        $params['TNT']               = $subscriber->getPlatforms()->filter(function (Platform $platform) {
            return $platform->getName() === Platform::TNT;
        })->count() > 0;
        $params['NETFLIX'] = $subscriber->getPlatforms()->filter(function (Platform $platform) {
            return $platform->getName() === Platform::NETFLIX;
        })->count() > 0;
        $params['PRIME'] = $subscriber->getPlatforms()->filter(function (Platform $platform) {
            return $platform->getName() === Platform::PRIME;
        })->count() > 0;
        $params['DISNEY'] = $subscriber->getPlatforms()->filter(function (Platform $platform) {
            return $platform->getName() === Platform::DISNEY;
        })->count() > 0;
        $params['CANAL'] = $subscriber->getPlatforms()->filter(function (Platform $platform) {
            return $platform->getName() === Platform::CANAL;
        })->count() > 0;

        return $params;
    }
}
