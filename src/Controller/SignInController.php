<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SignInController extends AbstractController
{
    #[Route('/sign-in', name: 'app_sign_in')]
    public function index(AuthenticationUtils $authenticationUtils, LoggerInterface $logger): Response
    {
        if ($this->isGranted('ROLE_USER') === true) {
            return $this->redirectToRoute('app_account');
        }

        // get the login error if there is one
        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error !== null) {
            $logger->error(
                'INVALID_LOGIN',
                [
                    'lastUsername' => $lastUsername,
                    'ip'           => $_SERVER['REMOTE_ADDR'],
                    'error'        => $error,
                ]
            );
        }

        return $this->render('sign_in/index.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }
}
