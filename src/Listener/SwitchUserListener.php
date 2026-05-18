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
    public const string SWITCH_USER_PARAMETER = 'switch_user';

    #[AsEventListener]
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        // get values
        $action = $this->getAction($event);
        $name = $this->getTargetUsername($event);
        $original = $this->getOriginalUsername($event);

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

    private function getAction(SwitchUserEvent $event): string
    {
        return $event->getRequest()->query->getString(self::SWITCH_USER_PARAMETER);
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
}
