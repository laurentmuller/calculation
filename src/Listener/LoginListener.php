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
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Listener for the user interactive login event.
 */
#[AsEventListener(event: LoginSuccessEvent::class, method: 'onLoginSuccess')]
class LoginListener implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use TranslatorFlashMessageAwareTrait;

    public function __construct(
        private readonly UserRepository $repository,
        #[Autowire('%app_version%')]
        private readonly string $appVersion,
        #[Autowire('%app_name%')]
        private readonly string $appName
    ) {
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $this->updateUser($user);
        $this->notify($user);
    }

    private function notify(UserInterface $user): void
    {
        $params = [
            '%user_name%' => $user->getUserIdentifier(),
            '%app_version%' => $this->appVersion,
            '%app_name%' => $this->appName,
        ];
        $this->successTrans('security.login.success', $params);
    }

    private function updateUser(UserInterface $user): void
    {
        if ($user instanceof User) {
            $user->updateLastLogin();
            $this->repository->flush();
        }
    }
}
