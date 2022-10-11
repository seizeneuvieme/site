<?php

namespace App\Controller;

use App\DTO\SubscriberChildCreate;
use App\DTO\SubscriberContactInfosUpdate;
use App\DTO\SubscriberEmailUpdate;
use App\DTO\SubscriberPasswordUpdate;
use App\DTO\SubscriberStreamingPlatformsUpdate;
use App\Entity\Child;
use App\Entity\Platform;
use App\Entity\Subscriber;
use App\Repository\ChildRepository;
use App\Service\CityService;
use App\Service\SendInBlueApiService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

#[Route('/mon-compte')]
class AccountController extends AbstractController
{
    public function __construct(
        private readonly SendInBlueApiService $sendInBlueApiService,
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'app_account')]
    public function index(): Response
    {
        return $this->render('account/index.html.twig', [
            'subscriber' => $this->getUser(),
        ]);
    }

    #[Route('/renvoi-code-activation', name: 'app_send_new_activation_code')]
    public function sendNewActivationCode(): Response
    {
        /**
         * @var Subscriber $subscriber
         */
        $subscriber = $this->getUser();

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            "{$subscriber->getId()}",
            "{$subscriber->getEmail()}",
            ['id' => $subscriber->getId()]
        );

        $template = $this->sendInBlueApiService->getTemplate(SendInBlueApiService::ACTIVE_ACCOUNT_TEMPLATE_ID);
        if ($template !== null) {
            $this->sendInBlueApiService->sendTransactionalEmail(
                $template,
                [
                    'name'  => $subscriber->getFirstname(),
                    'email' => $subscriber->getEmail(),
                ],
                [
                    'FIRSTNAME'  => $subscriber->getFirstname(),
                    'SIGNED_URL' => $signatureComponents->getSignedUrl(),
                ]
            );
        }

        $this->addFlash('send_new_activation_code', '');

        return $this->redirectToRoute('app_account');
    }

    #[Route('/modifier/email', name: 'app_update_email')]
    public function updateEmail(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('update-email', (string) $request->request->get('token'))) {
            $subscriberEmailUpdate = new SubscriberEmailUpdate();
            $subscriberEmailUpdate->hydrateFromData($request->request->all());

            $errors = $validator->validate($subscriberEmailUpdate);

            /**
             * @var Subscriber $subscriber
             */
            $subscriber = $this->getUser();
            if (0 < $errors->count()) {
                $this->logger->error(
                    'EMAIL_INVALID',
                    [
                        'email' => $subscriberEmailUpdate->email,
                        'user'  => $subscriber->getEmail(),
                    ]
                );

                $this->addFlash('error', '');

                return $this->render('account/update_email.html.twig');
            }

            $subscriber = $this->getUser();
            /**
             * @var Subscriber $subscriber
             */
            $subscriber->setEmail($subscriberEmailUpdate->email);
            $entityManager->flush();
            $this->logger->info(
                'EMAIL_UPDATED',
                [
                    'user' => $subscriber->getEmail(),
                ]
            );
            $this->addFlash('success', 'Ton adresse email a bien été modifiée 🎉');
        }

        return $this->render('account/update_email.html.twig');
    }

    #[Route('/modifier/mot-de-passe', name: 'app_update_password')]
    public function updatePassword(
        Request $request,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('update-password', (string) $request->request->get('token'))) {
            /**
             * @var Subscriber $subscriber
             */
            $subscriber = $this->getUser();

            $subscriberPasswordUpdate = new SubscriberPasswordUpdate();
            $subscriberPasswordUpdate->hydrateFromData($request->request->all());

            $errors = $validator->validate($subscriberPasswordUpdate);
            if (0 < $errors->count()) {
                $this->logger->error(
                    'PASSWORD_INVALID',
                    [
                        'user' => $subscriber->getEmail(),
                    ]
                );

                $this->addFlash('error', '');

                return $this->render('account/update_password.html.twig');
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
            $entityManager->flush();
            $this->logger->info(
                'PASSWORD_UPDATED',
                [
                    'user' => $subscriber->getEmail(),
                ]
            );
            $this->addFlash('success', 'Ton mot de passe a bien été modifiée 🎉');
        }

        return $this->render('account/update_password.html.twig');
    }

    #[Route('/modifier/coordonnees', name: 'app_update_user_infos')]
    public function updateUserInfos(
        Request $request,
        ValidatorInterface $validator,
        CityService $cityService,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('update-user-infos', (string) $request->request->get('token'))) {
            $subscriberContactInfosUpdate = new SubscriberContactInfosUpdate();
            $subscriberContactInfosUpdate->hydrateFromData($request->request->all());
            $cityService->processCityDetails($subscriberContactInfosUpdate);

            $errors = $validator->validate($subscriberContactInfosUpdate);
            if (0 < $errors->count()) {
                /**
                 * @var Subscriber $subscriber
                 */
                $subscriber = $this->getUser();
                $this->logger->error(
                    'USER_INFOS_INVALID',
                    [
                        'infos' => $subscriberContactInfosUpdate,
                        'user'  => $subscriber->getEmail(),
                    ]
                );

                $this->addFlash('error', '');

                return $this->render('account/update_user_infos.html.twig');
            }

            /**
             * @var Subscriber $subscriber
             */
            $subscriber = $this->getUser();
            $subscriber->setFirstname($subscriberContactInfosUpdate->firstname);
            $subscriber->setCity($subscriberContactInfosUpdate->city);
            $subscriber->setDepartmentNumber($subscriberContactInfosUpdate->departmentNumber);
            $subscriber->setDepartmentName($subscriberContactInfosUpdate->departmentName);
            $subscriber->setRegion($subscriberContactInfosUpdate->region);

            $entityManager->flush();
            $this->logger->info(
                'USER_INFOS_UPDATED',
                [
                    'user' => $subscriber->getEmail(),
                ]
            );
            $this->addFlash('success', 'Tes coordonnées ont bien été modifiées 🎉');
        }

        return $this->render('account/update_user_infos.html.twig');
    }

    #[Route('/modifier/plateformes', name: 'app_update_platforms')]
    public function updatePlatforms(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('update-platforms', (string) $request->request->get('token'))) {
            $subscriberStreamingPlatformsUpdate = new SubscriberStreamingPlatformsUpdate();
            $subscriberStreamingPlatformsUpdate->hydrateFromData($request->request->all());

            $errors = $validator->validate($subscriberStreamingPlatformsUpdate);
            if (0 < $errors->count()) {
                /**
                 * @var Subscriber $subscriber
                 */
                $subscriber = $this->getUser();
                $this->logger->error(
                    'PLATFORMS_INVALID',
                    [
                        'platforms' => $subscriberStreamingPlatformsUpdate,
                        'user'      => $subscriber->getEmail(),
                    ]
                );

                $this->addFlash('error', '');

                return $this->render('account/update_platforms.html.twig');
            }

            /**
             * @var Subscriber $subscriber
             */
            $subscriber = $this->getUser();
            $subscriber->getPlatforms()->clear();
            foreach ($subscriberStreamingPlatformsUpdate->streamingPlatforms as $streamingPlatform) {
                $platform = new Platform();
                $platform->setName($streamingPlatform);
                $subscriber->addPlatform($platform);
            }

            $entityManager->flush();
            $this->logger->info(
                'PLATFORMS_UPDATED',
                [
                    'user' => $subscriber->getEmail(),
                ]
            );
            $this->addFlash('success', 'Tes plateformes de streaming payantes ont bien été modifiées 🎉');
        }

        return $this->render('account/update_platforms.html.twig');
    }

    #[Route('/ajouter/enfant', name: 'app_add_child')]
    public function addChild(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('add-child', (string) $request->request->get('token'))) {
            $subscriberChildCreate = new SubscriberChildCreate();
            $subscriberChildCreate->hydrateFromData($request->request->all());

            $errors = $validator->validate($subscriberChildCreate);
            if (0 < $errors->count()) {
                /**
                 * @var Subscriber $subscriber
                 */
                $subscriber = $this->getUser();
                $this->logger->error(
                    'CHILD_INVALID',
                    [
                        'child' => $subscriberChildCreate,
                        'user'  => $subscriber->getEmail(),
                    ]
                );
                $this->addFlash('error', '');

                return $this->render('account/add_child.html.twig');
            }

            /**
             * @var Subscriber $subscriber
             */
            $subscriber = $this->getUser();
            $child      = new Child();
            $child->setFirstname($subscriberChildCreate->childFirstname);
            $child->setBirthDate($subscriberChildCreate->childBirthDate);
            $subscriber->addChild($child);
            $entityManager->flush();
            $this->logger->info(
                'CHILD_ADDED',
                [
                    'user' => $subscriber->getEmail(),
                ]
            );
            $this->addFlash('success', "✅ C'est noté ! Tu recevras désormais aussi des recommandations de films pour {$child->getFirstname()}");
        }

        return $this->render('account/add_child.html.twig');
    }

    #[Route('/supprimer/enfant', name: 'app_remove_child')]
    public function removeChild(
        Request $request,
        ChildRepository $childRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('remove-child', (string) $request->request->get('token'))) {
            /**
             * @var Subscriber $subscriber
             */
            $subscriber = $this->getUser();

            $child = $childRepository->findOneBy([
                'id' => $request->request->get('child-id'),
            ]);

            if ($child === null || $subscriber->getId() !== $child->getSubscriber()?->getId() || $subscriber->getChilds()->count() < 2) {
                return $this->redirectToRoute('app_account');
            }

            $subscriber->removeChild($child);
            $entityManager->remove($child);
            $entityManager->flush();
            $this->logger->info(
                'CHILD_REMOVED',
                [
                    'child' => $child,
                    'user'  => $subscriber->getEmail(),
                ]
            );
            $this->addFlash('success', "✅ C'est noté ! Tu recevras plus de recommandations de films pour {$child->getFirstname()}");
        }

        return $this->redirectToRoute('app_account');
    }

    #[Route('/supprimer/compte', name: 'app_remove_account')]
    public function removeAccount(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('remove-account', (string) $request->request->get('token'))) {
            /**
             * @var Subscriber $subscriber
             */
            $subscriber = $this->getUser();
            $password   = $request->request->get('password');

            /**
             * @var PasswordAuthenticatedUserInterface $subscriber
             */
            $isPasswordValid = $passwordHasher->isPasswordValid(
                $subscriber,
                (string) $password
            );

            /**
             * @var Subscriber $subscriber
             */
            if ($isPasswordValid === true) {
                $entityManager->remove($subscriber);
                $entityManager->flush();
                $this->logger->info(
                    'ACCOUNT_DELETED',
                    [
                        'user' => $subscriber->getEmail(),
                    ]
                );

                $template = $this->sendInBlueApiService->getTemplate(SendInBlueApiService::CONFIRM_ACCOUNT_REMOVED);
                if ($template !== null) {
                    $this->sendInBlueApiService->sendTransactionalEmail(
                        $template,
                        [
                            'name'  => $subscriber->getFirstname(),
                            'email' => $subscriber->getEmail(),
                        ],
                        [
                            'FIRSTNAME' => $subscriber->getFirstname(),
                        ]
                    );
                }

                $session = new Session();
                $session->invalidate();

                return $this->redirectToRoute('app_logout');
            }
            $this->addFlash('cant_remove_account', '');
        }

        return $this->redirectToRoute('app_account');
    }
}
