<?php

namespace App\Controller;

use App\DTO\CampaignCreate;
use App\DTO\CampaignUpdate;
use App\Entity\Campaign;
use App\Entity\Subscriber;
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
#[IsGranted('ROLE_ADMIN', null, null, Response::HTTP_NOT_FOUND)]
class BackofficeController extends AbstractController
{
    public function __construct(
        private SendInBlueApiService $sendInBlueApiService
    ) {
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/', name: 'app_backoffice')]
    #[IsGranted('ROLE_ADMIN', null, null, Response::HTTP_NOT_FOUND)]
    public function index(
        SubscriberRepository $subscriberRepository,
        CampaignRepository $campaignRepository
    ): Response {
        $nbOfSubscribers           = $subscriberRepository->getTotalNumberOfSubscribers();
        $nbOfSubscribersThisMonth  = $subscriberRepository->getNumberOfNewSubscribersThisMonth();
        $nbOfSubscribersForNetflix = $subscriberRepository->getNumberOfSubscribersForNetflix();
        $nbOfSubscribersForDisney  = $subscriberRepository->getNumberOfSubscribersForDisney();

        $pendingCampaigns = $campaignRepository->findBy([
            'state' => Campaign::DRAFT_STATE,
        ]);

        $lastSentCampaigns = $campaignRepository->findBy([
            'state' => Campaign::SENT_STATE,
        ], ['sendingDate' => 'DESC'], 10);

        return $this->render('backoffice/index.html.twig', [
            'nbOfSubscribers'           => $nbOfSubscribers,
            'nbOfSubscribersThisMonth'  => $nbOfSubscribersThisMonth,
            'nbOfSubscribersForNetflix' => $nbOfSubscribersForNetflix,
            'nbOfSubscribersForDisney'  => $nbOfSubscribersForDisney,
            'pendingCampaigns'          => $pendingCampaigns,
            'sentCampaigns'             => $lastSentCampaigns,
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
    ): Response {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('add-campaign', (string) $request->request->get('token'))) {
            $templateId = $request->request->get('template-id');

            $template = $this->sendInBlueApiService->getTemplate((int) $templateId);

            if ($template === null) {
                $this->addFlash('invalid_template_id', 'Identifiant invalide');
            }

            $campaign = new CampaignCreate();
            $campaign->hydrateFromData([
                'name'        => $template?->getName(),
                'templateId'  => $template?->getId(),
                'sendingDate' => $request->request->get('sending-date'),
            ]);

            $errors = $validator->validate($campaign);
            if ($errors->count() > 0) {
                $this->addFlash('invalid_form', '');

                return $this->render('backoffice/add_campaign.html.twig');
            }
            $campaign = $campaignService->createCampaignFromDTO($campaign);

            $entityManager->persist($campaign);
            $entityManager->flush();

            $this->addFlash('success', "Campagne {$campaign->getName()} créée 🎉");

            return $this->redirectToRoute('app_backoffice');
        }

        return $this->render('backoffice/add_campaign.html.twig');
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
    ): Response {
        $campaign = $campaignRepository->findOneBy([
            'id' => $id,
        ]);

        if ($campaign === null) {
            return $this->redirectToRoute('app_backoffice');
        }

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('update-campaign', (string) $request->request->get('token'))) {
            $campaignUpdate = new CampaignUpdate();
            $campaignUpdate->hydrateFromData($request->request->all());

            $errors = $validator->validate($campaign);
            if ($errors->count() > 0) {
                $this->addFlash('invalid_form', '');

                return $this->render('backoffice/update_campaign.html.twig');
            }

            $campaign->setSendingDate($campaignUpdate->sendingDate);
            $entityManager->flush();
            $this->addFlash('success', "La campagne {$campaign->getName()} a bien été reprogrammée pour le {$campaign->getSendingDate()->format('d/m/Y')} 🎉");
        }

        return $this->render('backoffice/update_campaign.html.twig', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/supprimer/campagne', name: 'app_remove_campaign')]
    public function removeCampaign(
        Request $request,
        EntityManagerInterface $entityManager,
        CampaignRepository $campaignRepository,
    ): Response {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('remove-campaign', (string) $request->request->get('token'))) {
            $campaign = $campaignRepository->findOneBy([
                'id' => $request->request->get('campaign_id'),
            ]);

            if ($campaign === null) {
                return $this->redirectToRoute('app_backoffice');
            }

            $entityManager->remove($campaign);
            $entityManager->flush();

            $this->addFlash('success', "La campagne {$campaign->getName()} a bien été supprimée 🎉");
        }

        return $this->redirectToRoute('app_backoffice');
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/test/campagne', name: 'app_send_campaign_mail_test')]
    public function sendCampaignMailTest(
        Request $request,
        CampaignRepository $campaignRepository,
        SendInBlueApiService $sendInBlueApiService,
        CampaignService $campaignService
    ): Response {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('test-campaign', (string) $request->request->get('token'))) {
            $campaign = $campaignRepository->findOneBy([
                'id' => $request->request->get('campaign_id'),
            ]);

            if ($campaign === null) {
                return $this->redirectToRoute('app_backoffice');
            }

            $template = $sendInBlueApiService->getTemplate($campaign->getTemplateId());
            if ($template !== null) {
                /**
                 * @var Subscriber $subscriber
                 */
                $subscriber = $this->getUser();
                $params     = $campaignService->createParams($subscriber);
                $result     = $sendInBlueApiService->sendTransactionalEmail($template, [
                    'name'  => $subscriber->getFirstname(),
                    'email' => $subscriber->getEmail(),
                ], $params);
                if ($result === true) {
                    $this->addFlash('success', "La campagne {$campaign->getName()} a bien été envoyée à {$subscriber->getEmail()} 🎉");
                } else {
                    $this->addFlash('error', "La campagne n'a pas pu être envoyée");
                }
            }
        }

        return $this->redirectToRoute('app_backoffice');
    }
}
