<?php

namespace App\Command;

use App\Entity\Campaign;
use App\Repository\CampaignRepository;
use App\Service\CampaignService;
use DateTime;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:send:email-campaign',
    description: 'Send scheduled email campaign',
)]
class SendEmailCampaignCommand extends Command
{
    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly CampaignService $campaignService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('BEGIN_SEND_EMAIL_CAMPAIGN_COMMAND');
        $campaigns = $this->campaignRepository->findBy([
            'state'       => Campaign::DRAFT_STATE,
            'sendingDate' => new DateTime('now'),
        ]);
        $nbCampaigns = count($campaigns);
        $this->logger->info('PROCESS_CAMPAIGNS', [
            'campaignsToSend' => $nbCampaigns,
        ]);

        foreach ($campaigns as $campaign) {
            $this->logger->info('PROCESS_CAMPAIGN', [
                'campaignId'   => $campaign->getId(),
                'campaignName' => $campaign->getName(),
            ]);
            try {
                $this->campaignService->processCampaign($campaign);
                $this->logger->info('CAMPAIGN_PROCESSED', [
                    'campaignId'   => $campaign->getId(),
                    'campaignName' => $campaign->getName(),
                ]);
            } catch (Exception $e) {
                $this->logger->error('PROCESS_CAMPAIGN_ERROR', [
                    'exception'    => $e,
                    'campaignId'   => $campaign->getId(),
                    'campaignName' => $campaign->getName(),
                ]);
                continue;
            }
        }
        $this->logger->info('END_SEND_EMAIL_CAMPAIGN_COMMAND');

        return Command::SUCCESS;
    }
}
