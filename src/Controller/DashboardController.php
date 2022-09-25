<?php

namespace App\Controller;

use App\DTO\Email;
use App\DTO\Password;
use App\Entity\Subscriber;
use App\Repository\SubscriberRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    #[Route('/modifier/email', name: 'app_update_email')]
    public function updateEmail(Request $request, ValidatorInterface $validator, SubscriberRepository $subscriberRepository, EntityManagerInterface $entityManager): Response
    {
        if($request->isMethod('POST')) {

            $email = new Email();
            $email->hydrateFromData($request->request->all());

            $errors = $validator->validate($email);
            if (0 < $errors->count()) {
                $this->addFlash('error', "");
                return $this->render('subscriber/update-email.html.twig');
            }

            $subscriber = $this->getUser();
            /**
             * @var Subscriber $subscriber
             */
            $subscriber->setEmail($email->email);
            $entityManager->flush();
            $this->addFlash('success', "Ton adresse email a bien été modifiée 🎉");
        }

        return $this->render('subscriber/update-email.html.twig');
    }

    #[Route('/modifier/mot-de-passe', name: 'app_update_password')]
    public function updatePassword(Request $request, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {

            $subscriber = $this->getUser();

            $password = new Password();
            $password->hydrateFromData($request->request->all());

            $errors = $validator->validate($password);
            if (0 < $errors->count()) {
                $this->addFlash('error', "");
                return $this->render('subscriber/update-password.html.twig');
            }

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $passwordHasher->hashPassword(
                $subscriber,
                $password->password
            );

            $subscriber->setPassword($encodedPassword);
            $entityManager->flush();
            $this->addFlash('success', "Ton mot de passe a bien été modifiée 🎉");
        }

        return $this->render('subscriber/update-password.html.twig');
    }

    #[Route('/modifier/coordonnees', name: 'app_update_user_infos')]
    public function updateUserInfos(Request $request, EntityManagerInterface $entityManager): Response
    {
        $subscriber = $this->getUser();

        if($request->isMethod('POST')) {
            $firstname = $request->request->get('firstname');
            $city = $request->request->get('city');
            $cityDetails = $request->request->get('city-details');

            $cityDetails = explode(',', $cityDetails);
            if (count($cityDetails) === 3) {
                $departmentNumber = trim($cityDetails[0]);
                $departmentName = trim($cityDetails[1]);
                $region = trim($cityDetails[2]);
            } else {

            }


            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $subscriber = $subscriberRepository->findOneBy([
                    'email' => $email
                ]);
                if (null === $subscriber) {
                    $subscriber = $this->getUser();
                    /**
                     * @var Subscriber $subscriber
                     */
                    $subscriber->setEmail($email);
                    $entityManager->flush();
                    $this->addFlash('success', "Ton adresse email a bien été modifiée 🎉");
                } else {
                    $this->addFlash('error', "Cette adresse email est déjà utilisée");
                }
            } else {
                $this->addFlash('error', "Adresse email invalide");
            }
        }

        return $this->render('subscriber/update-email.html.twig');
    }
}
