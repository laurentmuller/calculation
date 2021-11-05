<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
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
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\InvalidSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\WrongEmailVerifyException;

/**
 * Controller to register a new user.
 *
 * @author Laurent Muller
 */
class RegistrationController extends AbstractController
{
    use TargetPathTrait;

    /**
     * The register route name.
     */
    private const REGISTER_ROUTE = 'user_register';

    private EmailVerifier $verifier;

    public function __construct(EmailVerifier $verifier)
    {
        $this->verifier = $verifier;
    }

    /**
     * @Route("/register", name="user_register")
     */
    public function register(Request $request, UserPasswordHasherInterface $hasher, AuthenticationUtils $utils): Response
    {
        $user = new User();
        $form = $this->createForm(UserRegistrationType::class, $user);

        if ($this->handleRequestForm($request, $form)) {
            // encode the plain password
            $plainPassword = $form->get('plainPassword')->getData();
            $encodedPassword = $hasher->hashPassword($user, $plainPassword);
            $user->setPassword($encodedPassword);

            // save user
            $manager = $this->getManager();
            $manager->persist($user);
            $manager->flush();

            // generate a signed url and email it to the user
            $subject = $this->trans('registration.email.subject', ['%username%' => $user->getUsername()]);
            $email = (new TemplatedEmail())
                ->from($this->getAddressFrom())
                ->to((string) $user->getEmail())
                ->subject($subject)
                ->htmlTemplate('registration/email.html.twig');

            try {
                $this->verifier->sendEmailConfirmation('app_verify_email', $user, $email);

                return $this->redirectToHomePage();
            } catch (TransportException $e) {
                if ($request->hasSession()) {
                    $exception = new CustomUserMessageAuthenticationException('registration.send_email_error');
                    $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
                }

                return $this->redirectToRoute(self::REGISTER_ROUTE);
            }
        }

        return $this->renderForm('registration/register.html.twig', [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form,
        ]);
    }

    /**
     * @Route("/verify/email", name="app_verify_email")
     */
    public function verifyUserEmail(Request $request, UserRepository $repository): RedirectResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $id = $request->get('id');
        if (null === $id) {
            return $this->redirectToRoute(self::REGISTER_ROUTE);
        }

        $user = $repository->find((int) $id);
        if (!$user instanceof User) {
            return $this->redirectToRoute(self::REGISTER_ROUTE);
        }

        try {
            $this->verifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $e) {
            if ($request->hasSession()) {
                $exception = $this->translateException($e);
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }

            return $this->redirectToRoute(self::REGISTER_ROUTE);
        }

        $this->succesTrans('registration.confirmed', [' %username%' => (string) $user]);

        return $this->redirectToHomePage();
    }

    /**
     * Creeates a custom user exception.
     */
    private function createUserException(string $message, \Throwable $previous): CustomUserMessageAuthenticationException
    {
        return new CustomUserMessageAuthenticationException($message, [], 0, $previous);
    }

    /**
     * Translate the given exception.
     */
    private function translateException(VerifyEmailExceptionInterface $e): CustomUserMessageAuthenticationException
    {
        if ($e instanceof ExpiredSignatureException) {
            return $this->createUserException('registration.expired_signature', $e);
        }
        if ($e instanceof InvalidSignatureException) {
            return $this->createUserException('registration.invalid_signature', $e);
        }
        if ($e instanceof WrongEmailVerifyException) {
            return $this->createUserException('registration.wrong_email_verify', $e);
        }

        return $this->createUserException($e->getReason(), $e);
    }
}
