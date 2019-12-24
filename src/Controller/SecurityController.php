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

use App\Form\FosUserLoginType;
use App\Form\FosUserResetPasswordType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Security controller for displaying a custom login form and handle the reset password.
 *
 * @author Laurent Muller
 */
class SecurityController extends AbstractController
{
    /**
     * @var bool
     */
    private $debug;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * Constructor.
     *
     * @param CsrfTokenManagerInterface $tokenManager the token manager
     * @param KernelInterface           $kernel       the kernel
     */
    public function __construct(?CsrfTokenManagerInterface $tokenManager = null, KernelInterface $kernel)
    {
        $this->tokenManager = $tokenManager;
        $this->debug = $kernel->isDebug();
    }

    /**
     * Login action.
     */
    public function loginAction(Request $request): Response
    {
        /** @var Session $session */
        $session = $request->getSession();

        $authErrorKey = Security::AUTHENTICATION_ERROR;
        $lastUsernameKey = Security::LAST_USERNAME;

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get($lastUsernameKey);
        $csrfToken = $this->tokenManager ? $this->tokenManager->getToken('authenticate')->getValue() : null;

        $data = [
            '_username' => $lastUsername,
            '_remember_me' => $this->debug,
            '_csrf_token' => $csrfToken,
        ];

        // create
        $form = $this->createForm(FosUserLoginType::class, $data);

        // display form
        return $this->render('@FOSUser/Security/login.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
        ]);
    }

    /**
     * Shows the reset password form.
     */
    public function requestAction(Request $request, AuthenticationUtils $utils): Response
    {
        // get user name
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            $username = $user->getUsername();
        } else {
            $username = $request->get('username');
        }

        // parameters
        $error = $utils->getLastAuthenticationError();
        if (!$error instanceof AuthenticationException) {
            $error = null;
        }

        // create and display form
        $data = ['username' => $username];
        $form = $this->createForm(FosUserResetPasswordType::class, $data);

        return $this->render('@FOSUser/Resetting/request.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
        ]);
    }
}
