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
        $io->info("Email(s) sent : $numberEmailSent");
        $io->info("Email(s) in error : $numberOfErrors");
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

        $index = 1;
        $names = '';
        foreach ($ageGroupChilds as $ageGroupChild) {
            if ($index === 1) {
                $names = $ageGroupChild->getFirstname();
            } elseif ($index < count($ageGroupChilds)) {
                $names .= ', '.$ageGroupChild->getFirstname();
            } else {
                $names .= ' et '.$ageGroupChild->getFirstname();
            }
            ++$index;
        }

        return $names;
    }
}
