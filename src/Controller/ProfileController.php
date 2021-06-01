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
use App\Form\User\ProfileChangePasswordType;
use App\Form\User\ProfileEditType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for user profile.
 *
 * @author Laurent Muller
 *
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /**
     * Change password of the current user (if any).
     *
     * @Route("/change-password", name="user_profile_change_password")
     */
    public function changePassword(Request $request, UserPasswordHasherInterface $hasher): Response
    {
        // get user
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->errorTrans('profile.change_password.failure');

            return $this->redirectToHomePage();
        }

        // create and validate form
        $form = $this->createForm(ProfileChangePasswordType::class, $user);
        if ($this->handleRequestForm($request, $form)) {
            // update password
            $plainPassword = $form->get('plainPassword')->getData();
            $encodedPassword = $hasher->hashPassword($user, $plainPassword);
            $user->setPassword($encodedPassword);
            $this->getManager()->flush();

            $this->succesTrans('profile.change_password.success', ['%username%' => $user->getUsername()]);

            return $this->redirectToHomePage();
        }

        // display
        return $this->render('profile/profile_change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Edit the profile of the current user (if any).
     *
     * @Route("/edit", name="user_profile_edit")
     */
    public function editProfil(Request $request): Response
    {
        // get user
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->errorTrans('profile.edit.failure');

            return $this->redirectToHomePage();
        }

        // create and validate form
        $form = $this->createForm(ProfileEditType::class, $user);
        if ($this->handleRequestForm($request, $form)) {
            $this->getManager()->flush();
            $this->succesTrans('profile.edit.success', ['%username%' => $user->getUsername()]);

            return $this->redirectToHomePage();
        }

        // display
        return $this->render('profile/profile_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
