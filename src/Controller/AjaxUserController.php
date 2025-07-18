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

use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Entity\User;
use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
use App\Repository\UserRepository;
use App\Utils\StringUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for user XMLHttpRequest (Ajax) calls.
 */
#[Route(path: '/ajax/check/user', name: 'ajax_check_user_')]
class AjaxUserController extends AbstractController
{
    public function __construct(private readonly UserRepository $repository)
    {
    }

    /**
     * Check if a username or user e-mail exist.
     */
    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[GetRoute(path: IndexRoute::PATH, name: 'both')]
    public function checkBoth(#[MapQueryParameter] ?string $user = null): JsonResponse
    {
        $message = null;
        if (!StringUtils::isString($user)) {
            $message = 'username.blank';
        } elseif (!$this->findByUsernameOrEmail($user) instanceof User) {
            $message = 'username.not_found';
        }

        return $this->getJsonResponse($message);
    }

    /**
     * Check if a user e-mail already exists.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[GetRoute(path: '/email', name: 'email')]
    public function checkEmail(
        #[MapQueryParameter]
        ?string $email = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)]
        ?int $id = null,
    ): JsonResponse {
        $message = null;
        if (!StringUtils::isString($email)) {
            $message = 'email.blank';
        } elseif (\strlen($email) < UserInterface::MIN_USERNAME_LENGTH) {
            $message = 'email.short';
        } elseif (\strlen($email) > UserInterface::MAX_USERNAME_LENGTH) {
            $message = 'email.long';
        } else {
            $user = $this->findByEmail($email);
            if ($user instanceof User && $id !== $user->getId()) {
                $message = 'email.already_used';
            }
        }

        return $this->getJsonResponse($message);
    }

    /**
     * Check if a username already exists.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[GetRoute(path: '/name', name: 'name')]
    public function checkUsername(
        #[MapQueryParameter]
        ?string $username = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)]
        ?int $id = null,
    ): JsonResponse {
        $message = null;
        if (!StringUtils::isString($username)) {
            $message = 'username.blank';
        } elseif (\strlen($username) < UserInterface::MIN_USERNAME_LENGTH) {
            $message = 'username.short';
        } elseif (\strlen($username) > UserInterface::MAX_USERNAME_LENGTH) {
            $message = 'username.long';
        } else {
            $user = $this->findByUsername($username);
            if ($user instanceof User && $id !== $user->getId()) {
                $message = 'username.already_used';
            }
        }

        return $this->getJsonResponse($message);
    }

    private function findByEmail(string $email): ?User
    {
        return $this->repository->findByEmail($email);
    }

    private function findByUsername(string $username): ?User
    {
        return $this->repository->findByUsername($username);
    }

    private function findByUsernameOrEmail(string $usernameOrEmail): ?User
    {
        return $this->repository->findByUsernameOrEmail($usernameOrEmail);
    }

    private function getJsonResponse(?string $id = null): JsonResponse
    {
        if (null !== $id) {
            return $this->json($this->trans($id, [], 'validators'));
        }

        return $this->json(true);
    }
}
