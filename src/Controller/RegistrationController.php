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
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\UserExceptionService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * Controller to register a new user.
 */
#[AsController]
class RegistrationController extends AbstractController
{
    use TargetPathTrait;

    private const REGISTER_ROUTE = 'user_register';
    private const VERIFY_ROUTE = 'verify_email';

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
    #[Route(path: '/register', name: self::REGISTER_ROUTE)]
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

            // generate a signed url and email it to the user
            $email = (new TemplatedEmail())
                ->from($this->getAddressFrom())
                ->to((string) $user->getEmail())
                ->subject($this->trans('registration.subject'))
                ->htmlTemplate('registration/email.html.twig');

            try {
                $this->verifier->sendEmailConfirmation(self::VERIFY_ROUTE, $user, $email);

                return $this->redirectToHomePage();
            } catch (TransportExceptionInterface $e) {
                $this->service->handleException($request, $e);

                return $this->redirectToRoute(self::REGISTER_ROUTE);
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
    #[Route(path: '/verify/email', name: self::VERIFY_ROUTE)]
    public function verifyUserEmail(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $id = $this->getRequestInt($request, 'id');
        if (0 === $id) {
            return $this->redirectToRoute(self::REGISTER_ROUTE);
        }
        $user = $this->repository->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute(self::REGISTER_ROUTE);
        }

        try {
            $this->verifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $e) {
            $this->service->handleException($request, $e);

            return $this->redirectToRoute(self::REGISTER_ROUTE);
        }
        $this->successTrans('registration.confirmed', ['%username%' => $user->getUserIdentifier()]);

        return $this->redirectToHomePage();
    }
}
