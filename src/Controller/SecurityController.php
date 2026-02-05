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

use App\Attribute\ForPublicAccess;
use App\Attribute\ForUser;
use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Entity\User;
use App\Form\User\UserLoginType;
use App\Security\SecurityAttributes;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Controller for login user.
 */
class SecurityController extends AbstractController
{
    #[ForPublicAccess]
    #[GetPostRoute(path: '/login', name: SecurityAttributes::LOGIN_ROUTE)]
    public function login(#[CurrentUser] ?User $user, AuthenticationUtils $utils): Response
    {
        if ($user instanceof User) {
            return $this->redirectToHomePage();
        }
        $form = $this->createForm(UserLoginType::class, [
            SecurityAttributes::USER_FIELD => $utils->getLastUsername(),
            SecurityAttributes::REMEMBER_FIELD => true,
        ]);

        return $this->render('security/login.html.twig', [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form,
        ]);
    }

    #[ForUser]
    #[GetRoute(path: '/logout', name: SecurityAttributes::LOGOUT_ROUTE)]
    public function logout(): never
    {
        throw new \LogicException('This method should never be reached.');
    }

    #[ForPublicAccess]
    #[GetRoute(path: '/logout/success', name: SecurityAttributes::LOGOUT_SUCCESS_ROUTE)]
    public function logoutSuccess(): RedirectResponse
    {
        $this->successTrans('security.logout.success', ['%app_name%' => $this->getApplicationFull()]);

        return $this->redirectToRoute(SecurityAttributes::LOGIN_ROUTE);
    }
}
