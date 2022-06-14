<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\User\UserRegistrationType;
use App\Mime\RegistrationEmail;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\UserExceptionService;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * Controller to register a new user.
 */
#[AsController]
#[Route(path: '/register')]
class RegistrationController extends AbstractController
{
    private const ROUTE_REGISTER = 'user_register';
    private const ROUTE_VERIFY = 'user_verify';

    public function __construct(
        TranslatorInterface $translator,
        private readonly EmailVerifier $verifier,
        private readonly UserRepository $repository,
        private readonly UserExceptionService $service
    ) {
        parent::__construct($translator);
    }

    /**
     * Display and process form to register a new user.
     */
    #[Route(path: '', name: self::ROUTE_REGISTER)]
    public function register(Request $request, UserPasswordHasherInterface $hasher, AuthenticationUtils $utils): Response
    {
        $user = new User();
        $user->setPassword('fake');
        $form = $this->createForm(UserRegistrationType::class, $user);
        if ($this->handleRequestForm($request, $form)) {
            // encode password and save user
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $encodedPassword = $hasher->hashPassword($user, $plainPassword);
            $user->setPassword($encodedPassword);
            $this->repository->add($user);

            try {
                // generate a signed url and send email
                $email = $this->createEmail($user);
                $this->verifier->sendEmail(self::ROUTE_VERIFY, $user, $email);

                return $this->redirectToHomePage();
            } catch (TransportExceptionInterface $e) {
                $this->service->handleException($request, $e);

                return $this->redirectToRoute(self::ROUTE_REGISTER);
            }
        }

        return $this->renderForm('registration/register.html.twig', [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form,
        ]);
    }

    /**
     * Verify the user e-mail.
     */
    #[Route(path: '/verify', name: self::ROUTE_VERIFY)]
    public function verify(Request $request): RedirectResponse
    {
        // $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->findUser($request);
        if (!$user instanceof User) {
            return $this->redirectToRoute(self::ROUTE_REGISTER);
        }

        try {
            $this->verifier->handleEmail($request, $user);
        } catch (VerifyEmailExceptionInterface $e) {
            $this->service->handleException($request, $e);

            return $this->redirectToRoute(self::ROUTE_REGISTER);
        }
        $this->successTrans('registration.confirmed', ['%username%' => $user->getUserIdentifier()]);

        return $this->redirectToHomePage();
    }

    private function createEmail(User $user): RegistrationEmail
    {
        $email = new RegistrationEmail($this->translator);
        $email->subject($this->trans('registration.subject'))
            ->from($this->getAddressFrom())
            ->to((string) $user->getEmail())
            ->setFooterText($this->getFooterText())
            ->importance(NotificationEmail::IMPORTANCE_MEDIUM);

        return $email;
    }

    private function findUser(Request $request): ?User
    {
        if (0 !== $id = $this->getRequestInt($request, 'id')) {
            return $this->repository->find($id);
        }

        return null;
    }

    private function getFooterText(): string
    {
        return $this->trans('notification.footer', ['%name%' => $this->getApplicationName()]);
    }
}
