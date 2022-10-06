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
use Symfony\Component\Console\Style\SymfonyStyle;

class CampaignService
{
    public function __construct(
        private readonly SubscriberRepository $subscriberRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SendInBlueApiService $sendInBlueApiService
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
    public function processCampaign(Campaign $campaign, SymfonyStyle $io): void
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
                $io->error('Error: '.$e->getMessage());
                continue;
            }
        }

        $campaign->setNumberSent($numberEmailSent);
        $campaign->setState(Campaign::SENT_STATE);
        $this->entityManager->flush();
        $io->info("SubscriberEmailUpdate(s) sent : $numberEmailSent");
        $io->info("SubscriberEmailUpdate(s) in error : $numberOfErrors");
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

        $params['AGE_GROUP_1'] = '';
        $ageGroup1Childs       = $subscriber->getChilds()->filter(function (Child $child) {
            $age = date_diff($child->getBirthDate(), new \DateTime(date('Y-m-d')));

            return $age->format('%y') >= 3 && $age->format('%y') < 6;
        })->toArray();

        $index = 1;
        foreach ($ageGroup1Childs as $ageGroup1Child) {
            if ($index === 1) {
                $params['AGE_GROUP_1'] = $ageGroup1Child->getFirstname();
            } elseif ($index < count($ageGroup1Childs)) {
                $params['AGE_GROUP_1'] .= ', '.$ageGroup1Child->getFirstname();
            } else {
                $params['AGE_GROUP_1'] .= ' et '.$ageGroup1Child->getFirstname();
            }
            ++$index;
        }

        $params['AGE_GROUP_2'] = '';
        $ageGroup2Childs       = $subscriber->getChilds()->filter(function (Child $child) {
            $age = date_diff($child->getBirthDate(), new \DateTime(date('Y-m-d')));

            return $age->format('%y') >= 6 && $age->format('%y') < 12;
        })->toArray();

        $index = 1;
        foreach ($ageGroup2Childs as $key => $ageGroup2Child) {
            if ($index === 1) {
                $params['AGE_GROUP_2'] = $ageGroup2Child->getFirstname();
            } elseif ($key < count($ageGroup1Childs)) {
                $params['AGE_GROUP_2'] .= ', '.$ageGroup2Child->getFirstname();
            } else {
                $params['AGE_GROUP_2'] .= ' et '.$ageGroup2Child->getFirstname();
            }
            ++$index;
        }

        return $params;
    }
}
