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

use App\Traits\RequestTrait;
use App\Traits\TranslatorFlashMessageAwareTrait;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Listener for the switch user event.
 */
#[AsEventListener(event: SwitchUserEvent::class, method: 'onSwitchUser')]
class SwitchUserListener implements ServiceSubscriberInterface
{
    use RequestTrait;
    use ServiceSubscriberTrait;
    use TranslatorFlashMessageAwareTrait;

    /**
     * The exit value action.
     */
    private const EXIT_VALUE = '_exit';

    /**
     * The switch user parameter name.
     */
    private const SWITCH_USER = '_switch_user';

    /**
     * Handles the switch user event.
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        // get values
        $request = $event->getRequest();
        $name = $this->getTargetUsername($event);
        $original = $this->getOriginalUsername($event);
        $action = $this->getRequestString($request, self::SWITCH_USER);

        // get message
        if (self::EXIT_VALUE === $action) {
            $id = 'user.switch.exit.success';
        } elseif (null !== $original) {
            $id = 'user.switch.take.success';
        } else {
            $id = 'user.switch.take.default';
        }

        // display message
        $this->successTrans($id, [
            '%orignal%' => $original,
            '%name%' => $name,
        ]);
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
