<?php

namespace App\Controller;

use App\Repository\SubscriberRepository;
use App\Service\BrevoApiService;
use Brevo\Client\ApiException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/brevo-webhooks')]
class BrevoWebhooksController extends AbstractController
{
    public function __construct(
        private BrevoApiService $brevoApiService,
        private SubscriberRepository $subscriberRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @throws ApiException
     */
    #[Route('/unsubscribe', name: 'webhook_unsubscribe', methods: ['POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->logger->info('Unsubscribed event received from Brevo webhook', ['data' => $request->getContent()]);

        $data       = json_decode($request->getContent(), true);
        $email      = $data['email'];
        $subscriber = $this->subscriberRepository->findOneBy([
           'email' => $email,
        ]);

        if ($subscriber === null) {
            return new Response('OK', Response::HTTP_OK);
        }

        $entityManager->remove($subscriber);
        $entityManager->flush();
        $this->logger->info(
            'ACCOUNT_DELETED',
            [
                       'user' => $subscriber->getEmail(),
                   ]
        );
        $this->brevoApiService->deleteContact($subscriber->getBrevoContactId());
        $template = $this->brevoApiService->getTemplate(BrevoApiService::CONFIRM_ACCOUNT_REMOVED);
        if ($template !== null) {
            $this->brevoApiService->sendTransactionalEmail(
                $template,
                [
                           'name'  => $subscriber->getFirstname(),
                           'email' => $subscriber->getEmail(),
                       ],
                [
                           'FIRSTNAME' => $subscriber->getFirstname(),
                       ]
            );
        }

        return new Response('OK', Response::HTTP_OK);
    }
}
