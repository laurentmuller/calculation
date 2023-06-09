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
use App\Interfaces\RoleInterface;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for user XMLHttpRequest (Ajax) calls.
 */
#[AsController]
#[Route(path: '/ajax')]
class AjaxUserController extends AbstractController
{
    public function __construct(private readonly UserRepository $repository)
    {
    }

    /**
     * Check if a user e-mail already exists.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/check/user/email', name: 'ajax_check_user_email', methods: Request::METHOD_GET)]
    public function checkEmail(
        #[MapQueryParameter] string $email = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] int $id = null,
    ): JsonResponse {
        $message = null;
        if (empty($email)) {
            $message = 'email.blank';
        } elseif (\strlen($email) < User::MIN_USERNAME_LENGTH) {
            $message = 'email.short';
        } elseif (\strlen($email) > user::MAX_USERNAME_LENGTH) {
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
    #[Route(path: '/check/user/name', name: 'ajax_check_user_name', methods: Request::METHOD_GET)]
    public function checkName(
        #[MapQueryParameter] string $username = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)] int $id = null,
    ): JsonResponse {
        $message = null;
        if (empty($username)) {
            $message = 'username.blank';
        } elseif (\strlen($username) < User::MIN_USERNAME_LENGTH) {
            $message = 'username.short';
        } elseif (\strlen($username) > User::MAX_USERNAME_LENGTH) {
            $message = 'username.long';
        } else {
            $user = $this->findByUsername($username);
            if ($user instanceof User && $id !== $user->getId()) {
                $message = 'username.already_used';
            }
        }

        return $this->getJsonResponse($message);
    }

    /**
     * Check if a username or user e-mail exist.
     */
    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[Route(path: '/check/user', name: 'ajax_check_user', methods: Request::METHOD_GET)]
    public function checkUser(#[MapQueryParameter] string $user = null): JsonResponse
    {
        $message = null;
        if (empty($user)) {
            $message = 'username.blank';
        } elseif (!$this->findByUsernameOrEmail($user) instanceof User) {
            $message = 'username.not_found';
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

    private function getJsonResponse(string $id = null): JsonResponse
    {
        if (null !== $id) {
            return $this->json($this->trans($id, [], 'validators'));
        }

        return $this->json(true);
    }
}
