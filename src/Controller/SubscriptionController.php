<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SubscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'app_subscription')]
    public function index(): Response
    {
        return $this->render('subscription/index.html.twig');
    }
}
