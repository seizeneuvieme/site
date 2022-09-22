<?php

namespace App\Controller;

use App\DTO\Registration;
use App\Service\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SignUpController extends AbstractController
{
    #[Route('/inscription', name: 'app_sign_up')]
    public function index(Request $request, RegistrationService $registrationService, ValidatorInterface $validator, EntityManagerInterface $entityManager): Response
    {
        if($request->isMethod('POST')) {
            $registration = new Registration();
            $registration->hydrateFromData($request->request->all());
            if (true === $registrationService->doesUserAlreadyExist($registration)) {
                return $this->render('sign_up/index.html.twig', [
                    'user_already_exist' => $registration->email
                ]);
            }

            $registrationService->processCityDetails($registration);
            $errors = $validator->validate($registration);
            if ($errors->count() > 0) {
                return $this->render('sign_up/index.html.twig', [
                    'error' => true
                ]);
            }

            $subscriber = $registrationService->createSubscriberFromDTO($registration);
            $entityManager->persist($subscriber);
            $entityManager->flush();

            //TODO: send welcome email
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('sign_up/index.html.twig', [
            'email' => $request->query->get('email')
        ]);
    }
}
