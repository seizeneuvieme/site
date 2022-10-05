<?php

namespace App\Controller;

use App\DTO\SubscriberPasswordUpdate;
use App\Entity\Subscriber;
use App\Service\SendInBlueApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/mot-de-passe-oublie')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private SendInBlueApiService $sendInBlueApiService
    ) {
    }

    /**
     * Display & process form to request a password reset.
     */
    #[Route('/', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        if ($this->isGranted('ROLE_USER') === true) {
            return $this->redirectToRoute('app_account');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('_username') ?? '';

            return $this->processSendingPasswordResetEmail(
                "$email",
                $mailer,
                $translator
            );
        }

        return $this->render('reset_password/request.html.twig');
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/confirmation', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        if ($this->isGranted('ROLE_USER') === true) {
            return $this->redirectToRoute('app_account');
        }

        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route('/reinitialisation/{token}', name: 'app_reset_password')]
    public function reset(Request $request, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, string $token = null): Response
    {
        if ($this->isGranted('ROLE_USER') === true) {
            return $this->redirectToRoute('app_account');
        }

        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if ($token === null) {
            return $this->render('reset_password/reset.html.twig', [
                'error' => true,
            ]);
        }

        try {
            $subscriber = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->render('reset_password/reset.html.twig', [
                'error' => true,
            ]);
        }

        if ($request->isMethod('POST')) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            $subscriberPasswordUpdate = new SubscriberPasswordUpdate();
            $subscriberPasswordUpdate->hydrateFromData($request->request->all());

            $errors = $validator->validate($subscriberPasswordUpdate);
            if (0 < $errors->count()) {
                return $this->render('reset_password/reset.html.twig', [
                    'error' => true,
                ]);
            }

            // Encode(hash) the plain password, and set it.
            /**
             * @var PasswordAuthenticatedUserInterface $subscriber
             */
            $encodedPassword = $passwordHasher->hashPassword(
                $subscriber,
                $subscriberPasswordUpdate->password
            );

            /**
             * @var Subscriber $subscriber
             */
            $subscriber->setPassword($encodedPassword);
            $this->entityManager->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->render('reset_password/password_resetted.html.twig', [
                'resetPassword' => true,
            ]);
        }

        return $this->render('reset_password/reset.html.twig');
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator): RedirectResponse
    {
        $user = $this->entityManager->getRepository(Subscriber::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     '%s - %s',
            //     $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
            //     $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            // ));

            return $this->redirectToRoute('app_check_email');
        }

        $template = $this->sendInBlueApiService->getTemplate(SendInBlueApiService::RESET_PASSWORD_TEMPLATE_ID);
        if ($template !== null) {
            $this->sendInBlueApiService->sendTransactionalEmail(
                $template,
                [
                    'name'  => $user->getFirstname(),
                    'email' => $user->getEmail(),
                ],
                [
                    'SIGNED_URL' => $this->generateUrl('app_reset_password', [
                        'token' => $resetToken->getToken(),
                    ], UrlGenerator::ABSOLUTE_URL),
                ]
            );
        }

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}
