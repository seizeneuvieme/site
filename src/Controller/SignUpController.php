<?php

namespace App\Controller;

use App\DTO\SubscriberCreate;
use App\Repository\SubscriberRepository;
use App\Security\EmailVerifier;
use App\Service\CityService;
use App\Service\SubscriberService;
use App\Service\SendInBlueApiService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class SignUpController extends AbstractController
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private EmailVerifier $emailVerifier,
        private AuthenticatorInterface $loginAuthenticator,
        private SendInBlueApiService $sendInBlueApiService
    ){}

    #[Route('/inscription', name: 'app_sign_up')]
    public function index(
        Request                    $request,
        SubscriberService          $subscriberService,
        CityService                $cityService,
        ValidatorInterface         $validator,
        EntityManagerInterface     $entityManager,
        UserAuthenticatorInterface $userAuthenticator
    ): Response
    {
        if (true === $this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute("app_account");
        }

        if($request->isMethod('POST')) {
            $subscriberCreate = new SubscriberCreate();
            $subscriberCreate->hydrateFromData($request->request->all());
            if (true === $subscriberService->doesSubscriberAlreadyExist($subscriberCreate)) {
                return $this->render('sign_up/index.html.twig', [
                    'user_already_exist' => $subscriberCreate->email
                ]);
            }

            $cityService->processCityDetails($subscriberCreate);
            $errors = $validator->validate($subscriberCreate);
            if ($errors->count() > 0) {
                return $this->render('sign_up/index.html.twig', [
                    'error' => true
                ]);
            }

            $subscriber = $subscriberService->createSubscriberFromDTO($subscriberCreate);

            $entityManager->persist($subscriber);
            $entityManager->flush();

            $signatureComponents = $this->verifyEmailHelper->generateSignature(
                'app_verify_email',
                $subscriber->getId(),
                $subscriber->getEmail(),
                ['id' => $subscriber->getId()]
            );
            $template = $this->sendInBlueApiService->getTemplate(SendInBlueApiService::ACTIVE_ACCOUNT_TEMPLATE_ID);
            $this->sendInBlueApiService->sendTransactionalEmail(
                $template,
                [
                    'name' => $subscriber->getFirstname(),
                    'email' => $subscriber->getEmail()
                ],
                [
                    "FIRSTNAME" => $subscriber->getFirstname(),
                    "SIGNED_URL" => $signatureComponents->getSignedUrl()
                ]
            );

            $userAuthenticator->authenticateUser(
                $subscriber,
                $this->loginAuthenticator,
                $request
            );

            return $this->redirectToRoute('app_account');
        }

        return $this->render('sign_up/index.html.twig', [
            'email' => $request->query->get('email')
        ]);
    }

    #[Route('/verification/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        SubscriberRepository $subscriberRepository,
    ): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_sign_up');
        }

        $subscriber = $subscriberRepository->find($id);

        if (null === $subscriber) {
            return $this->redirectToRoute('app_sign_up');
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $subscriber);
            $this->addFlash('account_activated', "");
        } catch (Exception $exception) {
            $this->addFlash('verify_email_error', "");
        }

        return $this->redirectToRoute('app_account');
    }
}
