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

use App\Attribute\GetPostRoute;
use App\Entity\User;
use App\Form\AbstractEntityType;
use App\Form\User\ProfileEditType;
use App\Form\User\ProfilePasswordType;
use App\Interfaces\RoleInterface;
use App\Service\PasswordTooltipService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for the user profile.
 */
#[Route(path: '/user/profile', name: 'user_profile_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ProfileController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * Edit the profile.
     */
    #[GetPostRoute(path: '/edit', name: 'edit')]
    public function edit(#[CurrentUser] User $user, Request $request): Response
    {
        return $this->handleForm(
            $user,
            $request,
            ProfileEditType::class,
            'profile/profile_edit.html.twig',
            'profile.edit.success'
        );
    }

    /**
     * Edit the password.
     */
    #[GetPostRoute(path: '/password', name: 'password')]
    public function password(#[CurrentUser] User $user, Request $request, PasswordTooltipService $service): Response
    {
        return $this->handleForm(
            $user,
            $request,
            ProfilePasswordType::class,
            'profile/profile_password.html.twig',
            'profile.password.success',
            ['tooltips' => $service->getTooltips()]
        );
    }

    /**
     * @template T of AbstractEntityType<User>
     *
     * @param class-string<T> $type
     */
    private function handleForm(
        User $user,
        Request $request,
        string $type,
        string $template,
        string $message,
        array $parameters = []
    ): Response {
        $form = $this->createForm($type, $user);
        if ($this->handleRequestForm($request, $form)) {
            $this->manager->flush();

            return $this->redirectToHomePage($message, ['%username%' => $user]);
        }

        return $this->render($template, \array_merge(['form' => $form], $parameters));
    }
}
