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

namespace App\Listener;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Traits\TranslatorFlashMessageAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Listener for the user interactive login event.
 */
class LoginListener implements ServiceSubscriberInterface
{
    use ServiceMethodsSubscriberTrait;
    use TranslatorFlashMessageAwareTrait;

    public function __construct(
        private readonly UserRepository $repository,
        #[Autowire('%app_name_version%')]
        private readonly string $appNameVersion
    ) {
    }

    #[AsEventListener(event: LoginSuccessEvent::class)]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        $this->updateUser($user);
        $this->notify($user);
    }

    private function notify(User $user): void
    {
        $params = [
            '%user_name%' => $user->getUserIdentifier(),
            '%app_name%' => $this->appNameVersion,
        ];
        $this->successTrans('security.login.success', $params);
    }

    private function updateUser(User $user): void
    {
        $user->updateLastLogin();
        $this->repository->flush();
    }
}
