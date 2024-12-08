<?php

namespace App\Security;

use App\Entity\Subscriber;
use App\Service\BrevoApiService;
use Brevo\Client\ApiException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    public function __construct(
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly BrevoApiService $brevoApiService,
    ) {
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, UserInterface $user, TemplatedEmail $email): void
    {
        /**
         * @var Subscriber $user
         */
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            "{$user->getId()}",
            "{$user->getEmail()}",
            ['id' => $user->getId()]
        );

        $context                         = $email->getContext();
        $context['signedUrl']            = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey']  = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);
        $this->mailer->send($email);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     * @throws ApiException
     */
    public function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        /**
         * @var Subscriber $user
         */
        $this->verifyEmailHelper->validateEmailConfirmation(
            $request->getUri(),
            "{$user->getId()}",
            "{$user->getEmail()}"
        );

        $brevoCreateContactId = $this->brevoApiService->createUpdateContact($user);

        if ($brevoCreateContactId !== null) {
            $user->setBrevoContactId($brevoCreateContactId);
        }
        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
