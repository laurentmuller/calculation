<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRegistrationType;
use App\Security\EmailVerifier;
use App\Security\LoginFormAuthenticator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
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
    public function verifyUserEmail(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $this->verifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $e) {
            if ($request->hasSession()) {
                $exception = $this->handleResetException($e);
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }

            return $this->redirectToRoute('user_register');
        }

        $this->succesTrans('registration.confirmed', [' %username%' => $this->getUserName()]);

        return $this->redirectToHomePage();
    }

    /**
     * Translate the given exception.
     */
    private function handleResetException(VerifyEmailExceptionInterface $e): CustomUserMessageAuthenticationException
    {
        if ($e instanceof ExpiredSignatureException) {
            return new CustomUserMessageAuthenticationException('registration.expired_signature');
        }
        if ($e instanceof InvalidSignatureException) {
            return new CustomUserMessageAuthenticationException('registration.invalid_signature');
        }
        if ($e instanceof WrongEmailVerifyException) {
            return new CustomUserMessageAuthenticationException('registration.wrong_email_verify');
        }

        return new CustomUserMessageAuthenticationException($e->getReason());
    }
}
