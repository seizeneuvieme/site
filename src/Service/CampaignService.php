<?php

namespace App\Service;

use App\DTO\Campaign AS CampaignDTO;
use App\Entity\Campaign;
use App\Entity\Child;
use App\Entity\Platform;
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
    ){}

    public function createCampaignFromDTO(CampaignDTO $dto): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($dto->name);
        $campaign->setTemplateId($dto->templateId);
        $campaign->setSendingDate($dto->sendingDate);

        return $campaign;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function processCampaign(Campaign $campaign, SymfonyStyle $io): void
    {
        $template = $this->sendInBlueApiService->getTemplate($campaign->getTemplateId());

        if (null === $template) {
            throw new Exception("Template with id {$campaign->getTemplateId()} not found.");
        }

        $subscribers = $this->subscriberRepository->findBy([
            'isVerified' => true
        ]);

        $numberEmailSent = 0;
        $numberOfErrors = 0;

        foreach ($subscribers as $subscriber) {
            try {
                $params = [];
                $params['FIRSTNAME'] = $subscriber->getFirstname();
                $params['CITY'] = $subscriber->getCity();
                $params['DEPARTMENT_NAME'] = $subscriber->getDepartmentName();
                $params['DEPARTMENT_NUMBER'] = $subscriber->getDepartmentNumber();
                $params['REGION'] = $subscriber->getRegion();
                $params['DISNEY'] = $subscriber->getPlatforms()->filter(function(Platform $platform){ return $platform->getName() === Platform::DISNEY; })->count() > 0;
                $params['NETFLIX'] = $subscriber->getPlatforms()->filter(function(Platform $platform){ return $platform->getName() === Platform::NETFLIX; })->count() > 0;

                $params['AGE_GROUP_1'] = "";
                $ageGroup1Childs = $subscriber->getChilds()->filter(function(Child $child){
                    $age = date_diff($child->getBirthDate(), date_create(date("Y-m-d")));
                    return $age->format('%y') >= 3 &&  $age->format('%y') < 6;
                })->toArray();

                $index = 1;
                foreach ($ageGroup1Childs as $ageGroup1Child) {
                    if ($index === 1) {
                        $params['AGE_GROUP_1'] = $ageGroup1Child->getFirstname();
                    } else if ($index < count($ageGroup1Childs)) {
                        $params['AGE_GROUP_1'] .= ", " . $ageGroup1Child->getFirstname();
                    } else {
                        $params['AGE_GROUP_1'] .= " et " . $ageGroup1Child->getFirstname();
                    }
                    $index++;
                }

                $params['AGE_GROUP_2'] = "";
                $ageGroup2Childs = $subscriber->getChilds()->filter(function(Child $child){
                    $age = date_diff($child->getBirthDate(), date_create(date("Y-m-d")));
                    return $age->format('%y') >= 6 &&  $age->format('%y') < 12;
                })->toArray();

                $index = 1;
                foreach ($ageGroup2Childs as $key => $ageGroup2Child) {
                    if ($index === 1) {
                        $params['AGE_GROUP_2'] = $ageGroup2Child->getFirstname();
                    } else if ($key < count($ageGroup1Childs)) {
                        $params['AGE_GROUP_2'] .= ", " . $ageGroup2Child->getFirstname();
                    } else {
                        $params['AGE_GROUP_2'] .= " et " . $ageGroup2Child->getFirstname();
                    }
                    $index++;
                }

                //TODO: same for each group

                $result = $this->sendInBlueApiService->sendTransactionalEmail(
                    $template,
                    [
                        'name' => $subscriber->getFirstname(),
                        'email' => $subscriber->getEmail()
                    ],
                    $params
                );

                $result === true ? $numberEmailSent++ : $numberOfErrors++;
            } catch (Exception $e) {
                $io->error("Error: " . $e->getMessage());
                continue;
            }
        }

        $campaign->setNumberSent($numberEmailSent);
        $campaign->setState(Campaign::SENT_STATE);
        $this->entityManager->flush();
        $io->info("Email(s) sent : $numberEmailSent");
        $io->info("Email(s) in error : $numberOfErrors");
    }
}