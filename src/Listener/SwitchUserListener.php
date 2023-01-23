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
use Symfony\Component\HttpFoundation\Request;
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
     * The menu active key.
     */
    private const MENU_ACTIVE = 'menu_active';

    /**
     * The menus prefix.
     */
    private const MENU_PREFIX = 'menu_';

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

        // clear menus
        $this->clearMenus($request);

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

    private function clearMenus(Request $request): void
    {
        if ($request->hasSession()) {
            $session = $request->getSession();
            $values = \array_filter($session->all(), $this->filterKey(...), \ARRAY_FILTER_USE_KEY);
            /** @var string $key */
            foreach (\array_keys($values) as $key) {
                $session->remove($key);
            }
        }
    }

    private function filterKey(string $key): bool
    {
        return self::MENU_ACTIVE !== $key && \str_starts_with($key, self::MENU_PREFIX);
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
