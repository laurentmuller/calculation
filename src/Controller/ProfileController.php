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

use App\Attribute\GetPost;
use App\Entity\User;
use App\Form\User\ProfileChangePasswordType;
use App\Form\User\ProfileEditType;
use App\Interfaces\RoleInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for user profile.
 */
#[AsController]
#[Route(path: '/profile')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ProfileController extends AbstractController
{
    /**
     * Change password of the current user (if any).
     */
    #[GetPost(path: '/change-password', name: 'user_profile_change_password')]
    public function changePassword(Request $request, #[CurrentUser] User $user, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(ProfileChangePasswordType::class, $user);
        if ($this->handleRequestForm($request, $form)) {
            $manager->flush();

            return $this->redirectToHomePage(
                'profile.change_password.success',
                ['%username%' => $user->getUserIdentifier()],
                request: $request
            );
        }

        return $this->render('profile/profile_change_password.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Edit the profile of the current user (if any).
     */
    #[GetPost(path: '/edit', name: 'user_profile_edit')]
    public function editProfil(Request $request, #[CurrentUser] User $user, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(ProfileEditType::class, $user);
        if ($this->handleRequestForm($request, $form)) {
            $manager->flush();

            return $this->redirectToHomePage(
                'profile.edit.success',
                ['%username%' => $user->getUserIdentifier()],
                request: $request
            );
        }

        return $this->render('profile/profile_edit.html.twig', [
            'form' => $form,
        ]);
    }
}
