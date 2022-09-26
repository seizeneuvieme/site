<?php

namespace App\Controller;

use App\DTO\Child;
use App\DTO\Email;
use App\DTO\Password;
use App\DTO\StreamingPlatforms;
use App\DTO\UserInfos;
use App\Entity\Platform;
use App\Entity\Subscriber;
use App\Repository\ChildRepository;
use App\Security\EmailVerifier;
use App\Service\CityService;
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
    public function updateEmail(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): Response
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
    public function updatePassword(
        Request $request,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response
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
    public function updateUserInfos(
        Request $request,
        ValidatorInterface $validator,
        CityService $cityService,
        EntityManagerInterface $entityManager
    ): Response
    {
        if($request->isMethod('POST')) {
            $userInfos = new UserInfos();
            $userInfos->hydrateFromData($request->request->all());
            $cityService->processCityDetails($userInfos);

            $errors = $validator->validate($userInfos);
            if (0 < $errors->count()) {
                $this->addFlash('error', "");
                return $this->render('subscriber/update-user-infos.html.twig');
            }

            /**
             * @var Subscriber $subscriber
             */
            $subscriber = $this->getUser();
            $subscriber->setFirstname($userInfos->firstname);
            $subscriber->setCity($userInfos->city);
            $subscriber->setDepartmentNumber($userInfos->departmentNumber);
            $subscriber->setDepartmentName($userInfos->departmentName);
            $subscriber->setRegion($userInfos->region);

            $entityManager->flush();
            $this->addFlash('success', "Tes coordonnées ont bien été modifiées 🎉");
        }

        return $this->render('subscriber/update-user-infos.html.twig');
    }

    #[Route('/modifier/plateformes', name: 'app_update_platforms')]
    public function updatePlatforms(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): Response
    {
        if($request->isMethod('POST')) {
            $streamingPlatforms = new StreamingPlatforms();
            $streamingPlatforms->hydrateFromData($request->request->all());

            $errors = $validator->validate($streamingPlatforms);
            if (0 < $errors->count()) {
                $this->addFlash('error', "");
                return $this->render('subscriber/update-platforms.html.twig');
            }

            /**
             * @var Subscriber $subscriber
             */
            $subscriber = $this->getUser();
            $subscriber->getPlatforms()->clear();
            foreach ($streamingPlatforms->streamingPlatforms as $streamingPlatform) {
                $platform = new Platform();
                $platform->setName($streamingPlatform);
                $subscriber->addPlatform($platform);
            }

            $entityManager->flush();
            $this->addFlash('success', "Tes plateformes de streaming payantes ont bien été modifiées 🎉");
        }

        return $this->render('subscriber/update-platforms.html.twig');
    }

    #[Route('/ajouter/enfant', name: 'app_add_child')]
    public function addChild(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): Response
    {
        if($request->isMethod('POST')) {
            $newChild = new Child();
            $newChild->hydrateFromData($request->request->all());

            $errors = $validator->validate($newChild);
            if (0 < $errors->count()) {
                $this->addFlash('error', "");
                return $this->render('subscriber/add-child.html.twig');
            }

            /**
             * @var Subscriber $subscriber
             */
            $subscriber = $this->getUser();
            $child = new \App\Entity\Child();
            $child->setFirstname($newChild->childFirstname);
            $child->setBirthDate($newChild->childBirthDate);
            $subscriber->addChild($child);
            $entityManager->flush();
            $this->addFlash('success', "✅ C'est noté ! Tu recevras désormais des recommandations de films pour {$child->getFirstname()}");
        }

        return $this->render('subscriber/add-child.html.twig');
    }

    #[Route('/supprimer/enfant/{id}', name: 'app_remove_child')]
    public function removeChild(
        Request $request,
        ChildRepository $childRepository,
        EntityManagerInterface $entityManager,
        int $id
    ): Response
    {
        $subscriber = $this->getUser();

        $child = $childRepository->findOneBy([
            'id' => $id
        ]);

        if (null === $child || $subscriber->getId() !== $child->getSubscriber()->getId() || $subscriber->getChilds()->count() < 2) {
            return $this->redirectToRoute('app_dashboard');
        }

        $subscriber->removeChild($child);
        $entityManager->remove($child);
        $entityManager->flush();
        $this->addFlash('success', "✅ C'est noté ! Tu recevras plus de recommandations de films pour {$child->getFirstname()}");

        return $this->redirectToRoute('app_dashboard');
    }
}
