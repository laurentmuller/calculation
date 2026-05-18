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

use App\Traits\TranslatorFlashMessageAwareTrait;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener as SecuritySwitchUserListener;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Listener for the switch user event.
 */
class SwitchUserListener implements ServiceSubscriberInterface
{
    use ServiceMethodsSubscriberTrait;
    use TranslatorFlashMessageAwareTrait;

    /** The switch user parameter name. */
    public const string SWITCH_USER_PARAMETER = '_switch_user';

    private const string ROLE_PREVIOUS_ADMIN = 'ROLE_PREVIOUS_ADMIN';

    #[AsEventListener(event: SwitchUserEvent::class, priority: 100)]
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        // update the token
        $this->updateToken($event);

        // get values
        $request = $event->getRequest();
        $name = $this->getTargetUsername($event);
        $original = $this->getOriginalUsername($event);
        $action = $request->query->getString(self::SWITCH_USER_PARAMETER);

        // get the message
        if (SecuritySwitchUserListener::EXIT_VALUE === $action) {
            $id = 'user.switch.exit.success';
        } elseif (null !== $original) {
            $id = 'user.switch.take.success';
        } else {
            $id = 'user.switch.take.default';
        }

        // display message
        $this->successTrans($id, ['%name%' => $name, '%orignal%' => $original]);
    }

    private function getOriginalUsername(SwitchUserEvent $event): ?string
    {
        $token = $event->getToken();
        if ($token instanceof SwitchUserToken) {
            return $token->getOriginalToken()->getUserIdentifier();
        }

        return null;
    }

    private function getTargetUsername(SwitchUserEvent $event): string
    {
        return $event->getTargetUser()->getUserIdentifier();
    }

    /**
     * This is a workaround for https://github.com/symfony/symfony/issues/64224.
     */
    private function updateToken(SwitchUserEvent $event): void
    {
        $token = $event->getToken();
        if (!$token instanceof SwitchUserToken || !$token->getUser() instanceof UserInterface
            || \in_array(self::ROLE_PREVIOUS_ADMIN, $token->getRoleNames(), true)) {
            return;
        }

        $event->setToken(new SwitchUserToken(
            $token->getUser(),
            $token->getFirewallName(),
            \array_unique([...$token->getRoleNames(), self::ROLE_PREVIOUS_ADMIN]),
            $token->getOriginalToken(),
            $token->getOriginatedFromUri(),
        ));
    }
}
