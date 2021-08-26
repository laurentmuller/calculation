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

use App\Form\User\UserLoginType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Controller for login user.
 *
 * @author Laurent Muller
 */
class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $utils): Response
    {
        $username = $utils->getLastUsername();
        $error = $utils->getLastAuthenticationError();

        $form = $this->createForm(UserLoginType::class, [
            'username' => $username,
            'remember_me' => true,
        ]);

        // display form
        return $this->renderForm('security/login.html.twig', [
            'form' => $form,
            'error' => $error,
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank. It will be intercepted by the logout key on your firewall.');
    }
}
