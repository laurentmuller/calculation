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
use App\Form\User\UserLoginType;
use App\Interfaces\RoleInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
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
    private const LOGIN_ROUTE = 'app_login';

    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[Route(path: '/login', name: self::LOGIN_ROUTE, methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function login(#[CurrentUser] ?User $user, AuthenticationUtils $utils): Response
    {
        if ($user instanceof User) {
            return $this->redirectToHomePage();
        }
        $form = $this->createForm(UserLoginType::class, [
            'username' => $utils->getLastUsername(),
            'remember_me' => true,
        ]);

        return $this->render('security/login.html.twig', [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form,
        ]);
    }

    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/logout', name: 'app_logout', methods: Request::METHOD_GET)]
    public function logout(): never
    {
        throw new \LogicException('This method should never be reached.');
    }

    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[Route(path: '/logout/success', name: 'app_logout_success', methods: Request::METHOD_GET)]
    public function logoutSuccess(): RedirectResponse
    {
        $this->successTrans('security.logout.success', ['%app_name%' => $this->getApplicationName()]);

        return $this->redirectToRoute(self::LOGIN_ROUTE);
    }
}
