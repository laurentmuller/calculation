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

use App\Attribute\ForUser;
use App\Attribute\GetPostRoute;
use App\Entity\User;
use App\Form\AbstractEntityType;
use App\Form\User\ProfileEditType;
use App\Form\User\ProfilePasswordType;
use App\Model\TranslatableFlashMessage;
use App\Service\PasswordTooltipService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Controller for the user profile.
 */
#[ForUser]
#[Route(path: '/user/profile', name: 'user_profile_')]
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
            user: $user,
            request: $request,
            type: ProfileEditType::class,
            template: 'profile/profile_edit.html.twig',
            message: 'profile.edit.success'
        );
    }

    /**
     * Edit the password.
     */
    #[GetPostRoute(path: '/password', name: 'password')]
    public function password(#[CurrentUser] User $user, Request $request, PasswordTooltipService $service): Response
    {
        return $this->handleForm(
            user: $user,
            request: $request,
            type: ProfilePasswordType::class,
            template: 'profile/profile_password.html.twig',
            message: 'profile.password.success',
            parameters: ['tooltips' => $service->getTooltips()]
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
            if (!$this->isChangeSets($user)) {
                return $this->redirectToHomePage();
            }
            $this->manager->flush();

            return $this->redirectToHomePage(
                message: TranslatableFlashMessage::success(
                    message: $message,
                    parameters: ['%username%' => $user],
                )
            );
        }

        return $this->render($template, \array_merge(['form' => $form], $parameters));
    }

    private function isChangeSets(User $user): bool
    {
        $uow = $this->manager->getUnitOfWork();
        $uow->computeChangeSets();

        return [] !== $uow->getEntityChangeSet($user);
    }
}
