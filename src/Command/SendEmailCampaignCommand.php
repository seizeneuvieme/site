<?php

namespace App\Command;

use App\Entity\Campaign;
use App\Repository\CampaignRepository;
use App\Repository\SubscriberRepository;
use App\Service\CampaignService;
use DateTime;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send:email-campaign',
    description: 'Send scheduled email campaign',
)]
class SendEmailCampaignCommand extends Command
{

    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly SubscriberRepository $subscriberRepository,
        private readonly CampaignService $campaignService
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Searching for campaign to send...');

        $campaigns = $this->campaignRepository->findBy([
            'state' => Campaign::DRAFT_STATE,
            'sendingDate' => new DateTime('now')
        ]);
        $nbCampaigns = count($campaigns);

        $nbCampaigns === 0 ? $io->info("[OK] 0 campaign to send!") : $io->info("[OK] found $nbCampaigns campaign(s) to send!") ;

        foreach ($campaigns as $campaign) {
            $io->info("Sending campaign " . $campaign->getName() . "...");
            try {
                $this->campaignService->processCampaign($campaign, $io);
                $io->success("Campaign " . $campaign->getName() . " sent!");
            } catch (Exception $e) {
                $io->error("[NOK] Error: " . $e->getMessage());
                continue;
            }
        }
        return Command::SUCCESS;
    }
}
