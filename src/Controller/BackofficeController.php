<?php

namespace App\Controller;

use App\Repository\SubscriberRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BackofficeController extends AbstractController
{
    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/administration', name: 'app_backoffice')]
    #[IsGranted("ROLE_ADMIN",null,null,Response::HTTP_NOT_FOUND)]
    public function index(
        SubscriberRepository $subscriberRepository
    ): Response
    {
        $nbOfSubscribers = $subscriberRepository->getTotalNumberOfSubscribers();
        $nbOfSubscribersThisMonth = $subscriberRepository->getNumberOfNewSubscribersThisMonth();
        $nbOfSubscribersForNetflix = $subscriberRepository->getNumberOfSubscribersForNetflix();
        $nbOfSubscribersForDisney = $subscriberRepository->getNumberOfSubscribersForDisney();

        return $this->render('backoffice/index.html.twig', [
            'nbOfSubscribers' => $nbOfSubscribers,
            'nbOfSubscribersThisMonth' => $nbOfSubscribersThisMonth,
            'nbOfSubscribersForNetflix' => $nbOfSubscribersForNetflix,
            'nbOfSubscribersForDisney' => $nbOfSubscribersForDisney,
        ]);
    }
}
