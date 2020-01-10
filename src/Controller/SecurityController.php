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
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Security controller for displaying the login form and the reset password form.
 *
 * @author Laurent Muller
 */
class SecurityController extends AbstractController
{
    /**
     * Show the login form.
     */
    public function loginAction(Request $request, CsrfTokenManagerInterface $manager, KernelInterface $kernel, AuthenticationUtils $utils): Response
    {
        // create form
        $form = $this->createForm(FosUserLoginType::class, [
            '_username' => $this->getUserName($request, $utils),
            '_csrf_token' => $this->getCsrfToken($manager),
            '_remember_me' => $kernel->isDebug(),
        ]);

        // display form
        return $this->render('@FOSUser/Security/login.html.twig', [
            'form' => $form->createView(),
            'error' => $this->getLastAuthenticationError($utils),
        ]);
    }

    /**
     * Show the reset password form.
     */
    public function requestAction(Request $request, AuthenticationUtils $utils): Response
    {
        // create form
        $form = $this->createForm(FosUserResetPasswordType::class, [
            'username' => $this->getUserName($request, $utils),
        ]);

        // display form
        return $this->render('@FOSUser/Resetting/request.html.twig', [
            'form' => $form->createView(),
            'error' => $this->getLastAuthenticationError($utils),
        ]);
    }

    /**
     * Gets the authenticate Csrf token.
     *
     * @param CsrfTokenManagerInterface $manager the token manager
     *
     * @return string|null the Csrf token, if found; null otherwise
     */
    private function getCsrfToken(CsrfTokenManagerInterface $manager): ?string
    {
        return $manager->getToken('authenticate')->getValue();
    }

    /**
     * Gets the last authentication error.
     *
     * @param AuthenticationUtils $utils the utility to get error
     *
     * @return AuthenticationException|null the error, if found; null otherwise
     */
    private function getLastAuthenticationError(AuthenticationUtils $utils): ?AuthenticationException
    {
        $error = $utils->getLastAuthenticationError();
        if ($error instanceof AuthenticationException) {
            return $error;
        } else {
            // The value does not come from the security component.
            return null;
        }
    }

    /**
     * Gets the user name.
     *
     * @param Request the request
     * @param AuthenticationUtils the utility
     *
     * @return string|null the user name, if found; null otherwise
     */
    private function getUserName(Request $request, AuthenticationUtils $utils): ?string
    {
        if ($user = $this->getUser() instanceof UserInterface) {
            return $user->getUsername();
        } elseif ($userName = $utils->getLastUsername()) {
            return $userName;
        } else {
            return $request->get('username');
        }
    }
}
