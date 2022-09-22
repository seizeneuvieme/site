<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('subscriber/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }
}
