<?php

namespace App\Controller;

use App\DTO\Subscription;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SubscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'app_subscription')]
    public function index(Request $request, SubscriptionService $subscriptionService, ValidatorInterface $validator, EntityManagerInterface $entityManager): Response
    {
        if($request->isMethod('POST')) {
            $subscription = new Subscription();
            $subscription->hydrateFromData($request->request->all());
            if (true === $subscriptionService->doesUserAlreadyExist($subscription)) {
                $this->render('subscription/index.html.twig', [
                    'user_already_exist' => $subscription->email
                ]);
            }

            $subscriptionService->processCityDetails($subscription);
            $errors = $validator->validate($subscription);
            if ($errors->count() > 0) {
                $this->render('subscription/index.html.twig', [
                    'error' => true
                ]);
            }

            $subscriber = $subscriptionService->createSubscriberFromDTO($subscription);
            $entityManager->persist($subscriber);
            $entityManager->flush();

            //TODO: send welcome email
            //TODO: redirect to dashboard page
            return $this->redirectToRoute('app_subscriber');
        }

        return $this->render('subscription/index.html.twig');
    }
}
