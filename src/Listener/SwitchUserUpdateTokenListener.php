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

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

/**
 * This is a workaround for https://github.com/symfony/symfony/pull/64213 and must be deleted when the fix is released.
 */
class SwitchUserUpdateTokenListener
{
    private const string ROLE_PREVIOUS_ADMIN = 'ROLE_PREVIOUS_ADMIN';

    #[AsEventListener(priority: 100)]
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $token = $event->getToken();
        if (!$token instanceof SwitchUserToken || !$token->getUser() instanceof UserInterface) {
            return;
        }

        $roles = $token->getRoleNames();
        if (\in_array(self::ROLE_PREVIOUS_ADMIN, $roles, true)) {
            return;
        }

        $event->setToken(new SwitchUserToken(
            $token->getUser(),
            $token->getFirewallName(),
            \array_unique([...$roles, self::ROLE_PREVIOUS_ADMIN]),
            $token->getOriginalToken(),
            $token->getOriginatedFromUri(),
        ));
    }
}
