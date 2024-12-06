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

use App\Attribute\Get;
use App\Attribute\GetPost;
use App\Entity\User;
use App\Form\User\UserLoginType;
use App\Interfaces\RoleInterface;
use App\Security\SecurityAttributes;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Controller for login user.
 */
#[AsController]
class SecurityController extends AbstractController
{
    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[GetPost(path: '/login', name: SecurityAttributes::LOGIN_ROUTE)]
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

    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/logout', name: SecurityAttributes::LOGOUT_ROUTE)]
    public function logout(): never
    {
        throw new \LogicException('This method should never be reached.');
    }

    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[Get(path: '/logout/success', name: SecurityAttributes::LOGOUT_SUCCESS_ROUTE)]
    public function logoutSuccess(): RedirectResponse
    {
        $this->successTrans('security.logout.success', ['%app_name%' => $this->getApplicationName()]);

        return $this->redirectToRoute(SecurityAttributes::LOGIN_ROUTE);
    }
}
