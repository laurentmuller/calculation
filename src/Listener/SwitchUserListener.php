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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Listener for the switch user event.
 */
class SwitchUserListener implements EventSubscriberInterface, ServiceSubscriberInterface
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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [SwitchUserEvent::class => 'onSwitchUser'];
    }

    /**
     * Handles the switch user event.
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        // get values
        $request = $event->getRequest();
        $action = $this->getRequestString($request, self::SWITCH_USER);
        $original = $this->getOriginalUsername($event);
        $name = $this->getTargetUsername($event);

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
            $values = \array_filter($session->all(), fn (string $key): bool => $this->filterKey($key), \ARRAY_FILTER_USE_KEY);
            /** @var string $key */
            foreach (\array_keys($values) as $key) {
                $session->remove($key);
            }
        }
    }

    private function filterKey(string $key): bool
    {
        return \str_starts_with($key, self::MENU_PREFIX) && self::MENU_ACTIVE !== $key;
    }

    /**
     * Gets the original username (if any).
     */
    private function getOriginalUsername(SwitchUserEvent $event): ?string
    {
        $token = $event->getToken();
        if ($token instanceof SwitchUserToken) {
            return $token->getOriginalToken()->getUserIdentifier();
        }

        return null;
    }

    /**
     * Gets the target username.
     */
    private function getTargetUsername(SwitchUserEvent $event): string
    {
        return $event->getTargetUser()->getUserIdentifier();
    }
}
