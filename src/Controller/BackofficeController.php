<?php

namespace App\Controller;

use App\DTO\Campaign AS CampaignDTO;
use App\DTO\CampaignUpdate;
use App\Entity\Campaign;
use App\Repository\CampaignRepository;
use App\Repository\SubscriberRepository;
use App\Service\CampaignService;
use App\Service\SendInBlueApiService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/administration')]
#[IsGranted("ROLE_ADMIN",null,null,Response::HTTP_NOT_FOUND)]
class BackofficeController extends AbstractController
{

    public function __construct(
        private SendInBlueApiService $sendInBlueApiService
    ){}

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/', name: 'app_backoffice')]
    #[IsGranted("ROLE_ADMIN",null,null,Response::HTTP_NOT_FOUND)]
    public function index(
        SubscriberRepository $subscriberRepository,
        CampaignRepository $campaignRepository
    ): Response
    {
        $nbOfSubscribers = $subscriberRepository->getTotalNumberOfSubscribers();
        $nbOfSubscribersThisMonth = $subscriberRepository->getNumberOfNewSubscribersThisMonth();
        $nbOfSubscribersForNetflix = $subscriberRepository->getNumberOfSubscribersForNetflix();
        $nbOfSubscribersForDisney = $subscriberRepository->getNumberOfSubscribersForDisney();

        $pendingCampaigns = $campaignRepository->findBy([
            'state' => Campaign::DRAFT_STATE
        ]);

        $lastSentCampaigns = $campaignRepository->findBy([
            'state' => Campaign::SENT_STATE
        ], ['sendingDate' => 'DESC'], 10);

        return $this->render('backoffice/index.html.twig', [
            'nbOfSubscribers' => $nbOfSubscribers,
            'nbOfSubscribersThisMonth' => $nbOfSubscribersThisMonth,
            'nbOfSubscribersForNetflix' => $nbOfSubscribersForNetflix,
            'nbOfSubscribersForDisney' => $nbOfSubscribersForDisney,
            'pendingCampaigns' => $pendingCampaigns,
            'sentCampaigns' => $lastSentCampaigns
        ]);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/ajouter/campagne', name: 'app_add_campaign')]
    public function addCampaign(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        CampaignService $campaignService
    ): Response
    {
        if ($request->isMethod('POST')) {
            $templateId = $request->request->get('template-id');

            $template = $this->sendInBlueApiService->getTemplate((int)$templateId);

            if (null === $template) {
                $this->addFlash('invalid_template_id', "Identifiant invalide");
            }

            $campaign = new CampaignDTO();
            $campaign->hydrateFromData([
                'name' => $template?->getName(),
                'templateId' => $template?->getId(),
                'sendingDate' => $request->request->get('sending-date')
            ]);

            $errors = $validator->validate($campaign);
            if ($errors->count() > 0) {
                $this->addFlash('invalid_form', "");
                return $this->render('backoffice/add-campaign.html.twig');
            }
            $campaign = $campaignService->createCampaignFromDTO($campaign);

            $entityManager->persist($campaign);
            $entityManager->flush();

            $this->addFlash('success', "Campagne {$campaign->getName()} créée 🎉");
            return $this->redirectToRoute("app_backoffice");
        }
        return $this->render('backoffice/add-campaign.html.twig');
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/modifier/campagne/{id}', name: 'app_update_campaign')]
    public function updateCampaign(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        CampaignRepository $campaignRepository,
        int $id
    ): Response
    {
        $campaign = $campaignRepository->findOneBy([
            'id' => $id
        ]);

        if (null === $campaign) {
            return $this->redirectToRoute('app_backoffice');
        }

        if ($request->isMethod('POST')) {
            $campaignUpdate = new CampaignUpdate();
            $campaignUpdate->hydrateFromData($request->request->all());

            $errors = $validator->validate($campaign);
            if ($errors->count() > 0) {
                $this->addFlash('invalid_form', "");
                return $this->render('backoffice/update-campaign.html.twig');
            }

            $campaign->setSendingDate($campaignUpdate->sendingDate);
            $entityManager->flush();
            $this->addFlash("success", "La campagne {$campaign->getName()} a bien été reprogrammée pour le {$campaign->getSendingDate()->format('d/m/Y')} 🎉");
        }

        return $this->render('backoffice/update-campaign.html.twig', [
            "campaign" => $campaign
        ]);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/supprimer/campagne/{id}', name: 'app_remove_campaign')]
    public function removeCampaign(
        EntityManagerInterface $entityManager,
        CampaignRepository $campaignRepository,
        int $id
    ): Response
    {
        $campaign = $campaignRepository->findOneBy([
            'id' => $id
        ]);

        if (null === $campaign) {
            return $this->redirectToRoute('app_backoffice');
        }

        $entityManager->remove($campaign);
        $entityManager->flush();

        $this->addFlash("success", "La campagne {$campaign->getName()} a bien été supprimée 🎉");
        return $this->redirectToRoute("app_backoffice");
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/test/campagne/{id}', name: 'app_send_campaign_mail_test')]
    public function sendCampaignMailTest(
        CampaignRepository $campaignRepository,
        SendInBlueApiService $sendInBlueApiService,
        int $id
    ): Response
    {
        $campaign = $campaignRepository->findOneBy([
            'id' => $id
        ]);

        if (null === $campaign) {
            return $this->redirectToRoute('app_backoffice');
        }

        $template = $sendInBlueApiService->getTemplate($campaign->getTemplateId());
        $result = $sendInBlueApiService->sendTransactionalEmail($template, [
            'name' => $this->getUser()->getFirstname(),
            'email' => $this->getUser()->getEmail()
        ]);
        if (true === $result) {
            $this->addFlash("success", "La campagne {$campaign->getName()} a bien été envoyée à {$this->getUser()->getEmail()} 🎉");
        } else {
            $this->addFlash("error", "La campagne {$campaign->getName()} a bien été envoyée à {$this->getUser()->getEmail()} 🎉");
        }
        return $this->redirectToRoute("app_backoffice");
    }
}
