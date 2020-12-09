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
use App\Security\EmailVerifier;
use App\Security\LoginFormAuthenticator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
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
    private EmailVerifier $verifier;

    public function __construct(EmailVerifier $verifier)
    {
        $this->verifier = $verifier;
    }

    /**
     * @Route("/register", name="user_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder, GuardAuthenticatorHandler $handler, LoginFormAuthenticator $authenticator, AuthenticationUtils $utils): Response
    {
        $user = new User();
        $form = $this->createForm(UserRegistrationType::class, $user);

        if ($this->handleRequestForm($request, $form)) {
            // encode the plain password
            $plainPassword = $form->get('plainPassword')->getData();
            $encodedPassword = $encoder->encodePassword($user, $plainPassword);
            $user->setPassword($encodedPassword);

            $manager = $this->getManager();
            $manager->persist($user);
            $manager->flush();

            // generate a signed url and email it to the user
            $subject = $this->trans('registration.email.subject', ['%username%' => $user->getUsername()]);
            $email = (new TemplatedEmail())
                ->from($this->getAddressFrom())
                ->to($user->getEmail())
                ->subject($subject)
                ->htmlTemplate('registration/email.html.twig');

            try {
                $this->verifier->sendEmailConfirmation('app_verify_email', $user, $email);

                return $handler->authenticateUserAndHandleSuccess($user, $request, $authenticator, 'main');
            } catch (TransportException $e) {
                if ($request->hasSession()) {
                    $exception = new CustomUserMessageAuthenticationException('registration.send_email_error');
                    $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
                }

                return $this->redirectToRoute('user_register');
            }
        }

        return $this->render('registration/register.html.twig', [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify/email", name="app_verify_email")
     */
    public function verifyUserEmail(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $this->verifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $e) {
            if ($request->hasSession()) {
                $exception = $this->translateException($e);
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }

            return $this->redirectToRoute('user_register');
        }

        $this->succesTrans('registration.confirmed', [' %username%' => $this->getUserName()]);

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
