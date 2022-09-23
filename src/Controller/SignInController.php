<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SignInController extends AbstractController
{
    #[Route('/connexion', name: 'app_sign_in')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        if (true === $this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute("app_dashboard");
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('sign_in/index.html.twig', [
            'last_username'   => $lastUsername,
            'error'           => $error,
        ]);
    }
}
