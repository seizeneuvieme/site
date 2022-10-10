<?php

namespace App\Service;

use App\DTO\CampaignCreate;
use App\Entity\Campaign;
use App\Entity\Child;
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
     * @throws \Doctrine\DBAL\Exception
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
                    $params['AGE_3'] === '' &&
                    $params['AGE_4'] === '' &&
                    $params['AGE_5'] === '' &&
                    $params['AGE_6'] === '' &&
                    $params['AGE_7'] === '' &&
                    $params['AGE_8'] === '' &&
                    $params['AGE_9'] === '' &&
                    $params['AGE_10'] === '' &&
                    $params['AGE_11'] === '' &&
                    $params['AGE_12'] === ''
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
        $params['DISNEY']            = $subscriber->getPlatforms()->filter(function (Platform $platform) {
            return $platform->getName() === Platform::DISNEY;
        })->count() > 0;
        $params['NETFLIX'] = $subscriber->getPlatforms()->filter(function (Platform $platform) {
            return $platform->getName() === Platform::NETFLIX;
        })->count() > 0;

        for ($age = 3; $age <= 12; ++$age) {
            $params["AGE_$age"] = $this->createGroup($age, $subscriber);
        }

        return $params;
    }

    private function createGroup(int $age, Subscriber $subscriber): string
    {
        $ageGroupChilds = $subscriber->getChilds()->filter(function (Child $child) use ($age) {
            $childAge = date_diff($child->getBirthDate(), new \DateTime(date('Y-m-d')));

            return $childAge->format('%y') == $age;
        })->toArray();

        $names = '';
        foreach ($ageGroupChilds as $ageGroupChild) {
            $names .= ' ✅ '.$ageGroupChild->getFirstname();
        }

        return $names;
    }
}
