<?php

namespace App\Controller;

use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mon-compte')]
class DashboardController extends AbstractController
{

    public function __construct(
        private EmailVerifier $emailVerifier
    ){}

    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('subscriber/index.html.twig', [
            'subscriber' => $this->getUser()
        ]);
    }

    #[Route('/renvoi-code-activation', name: 'app_send_new_activation_code')]
    public function sendNewActivationCode(): Response
    {
        $subscriber = $this->getUser();

        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $subscriber,
            (new TemplatedEmail())
                ->from(new Address('fanny@lerehausseur.fr', 'Fanny - Le Réhausseur'))
                ->to($subscriber->getEmail())
                ->subject('Confirme ton email pour valider ton inscription')
                ->htmlTemplate('sign_up/confirmation_email.html.twig')
                ->context([
                    'subscriber' => $subscriber
                ])
        );

        $this->addFlash('send_new_activation_code', "");

        return $this->redirectToRoute('app_dashboard');
    }
}
