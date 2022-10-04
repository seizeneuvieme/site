<?php

namespace App\Service;

use App\DTO\Campaign AS CampaignDTO;
use App\Entity\Campaign;
use App\Entity\Child;
use App\Entity\Platform;
use App\Repository\CampaignRepository;
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
    public function processCampaign(Campaign $campaign, SymfonyStyle $io): int
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
                    return $age->format('y') >= 3 &&  $age->format('y') < 6;
                })->toArray();

                foreach ($ageGroup1Childs as $key => $ageGroup1Child) {
                    if ($key < count($ageGroup1Childs)) {
                        $params['AGE_GROUP_1'] .= ", " . $ageGroup1Child['firstname'];
                    } else {
                        $params['AGE_GROUP_1'] .= " et " . $ageGroup1Child['firstname'];
                    }
                }

                //TODO: same for each group

                $params['BIRTHDAYS'] = "";
                $lastCampaign = $this->entityManager
                    ->getConnection()
                    ->executeQuery("
                        SELECT * 
                        FROM campaign
                        WHERE state = :state
                        ORDER BY send_date DESC
                        LIMIT 1;
                    ", [
                       'state' => Campaign::SENT_STATE
                    ])->fetchOne();

                if (false !== $lastCampaign) {
                    $birthdayChilds = $subscriber->getChilds()->filter(function(Child $child) use ($campaign, $lastCampaign) {
                        return $child->getBirthDate() > $lastCampaign->getSendingDate()
                            && $child->getBirthDate() < $campaign->getSendingDate();
                    })->toArray();

                    foreach ($birthdayChilds as $key => $birthdayChild) {
                        if ($key < count($birthdayChilds)) {
                            $params['BIRTHDAYS'] .= ", " . $birthdayChild['firstname'];
                        } else {
                            $params['BIRTHDAYS'] .= " et " . $birthdayChild['firstname'];
                        }
                    }
                }

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
        $this->entityManager->flush();
        $io->success("Email(s) sent : $numberEmailSent");
        $io->success("Email(s) in error : $numberOfErrors");
    }
}