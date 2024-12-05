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
    /**
     * The authentication token name.
     */
    public const AUTHENTICATE_TOKEN = 'authenticate';

    /**
     * The login route name.
     */
    public const LOGIN_ROUTE = 'app_login';

    /**
     * The login token name.
     */
    public const LOGIN_TOKEN = 'login_token';

    /**
     * The logout route name.
     */
    public const LOGOUT_ROUTE = 'app_logout';

    /**
     * The logout token name.
     */
    public const LOGOUT_TOKEN = 'logout_token';

    /**
     * The logout success route name.
     */
    public const SUCCESS_ROUTE = 'app_logout_success';

    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[GetPost(path: '/login', name: self::LOGIN_ROUTE)]
    public function login(#[CurrentUser] ?User $user, AuthenticationUtils $utils): Response
    {
        if ($user instanceof User) {
            return $this->redirectToHomePage();
        }
        $form = $this->createForm(UserLoginType::class, [
            UserLoginType::USER_FIELD => $utils->getLastUsername(),
            UserLoginType::REMEMBER_FIELD => true,
        ]);

        return $this->render('security/login.html.twig', [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form,
        ]);
    }

    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/logout', name: self::LOGOUT_ROUTE)]
    public function logout(): never
    {
        throw new \LogicException('This method should never be reached.');
    }

    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[Get(path: '/logout/success', name: self::SUCCESS_ROUTE)]
    public function logoutSuccess(): RedirectResponse
    {
        $this->successTrans('security.logout.success', ['%app_name%' => $this->getApplicationName()]);

        return $this->redirectToRoute(self::LOGIN_ROUTE);
    }
}
