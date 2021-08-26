<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Listener;

use App\Traits\TranslatorFlashMessageTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener for the switch user event.
 *
 * @author Laurent Muller
 */
class SwitchUserListener implements EventSubscriberInterface
{
    use TranslatorFlashMessageTrait;

    /**
     * The exit value action.
     */
    private const EXIT_VALUE = '_exit';

    /**
     * The switch user parameter name.
     */
    private const SWITCH_USER = '_switch_user';

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public static function getSubscribedEvents()
    {
        return [SwitchUserEvent::class => 'onSwitchUser'];
    }

    /**
     * Handles the switch user event.
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        // session?
        $request = $event->getRequest();
        if (!$this->setSessionFromRequest($request)) {
            return;
        }

        // get values
        $action = $request->get(self::SWITCH_USER);
        $name = $this->getTargetUsername($event);
        $original = $this->getOriginalUsername($event);

        // get message
        if (self::EXIT_VALUE === $action) {
            $id = 'user.switch.exit.sucess';
        } elseif (null !== $original) {
            $id = 'user.switch.take.sucess';
        } else {
            $id = 'user.switch.take.default';
        }

        //display message
        $this->succesTrans($id, [
            '%orignal%' => (string) $original,
            '%name%' => (string) $name,
        ]);
    }

    /**
     * Gets the original user name (if any).
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
     * Gets the target user name.
     */
    private function getTargetUsername(SwitchUserEvent $event): string
    {
        return $event->getTargetUser()->getUserIdentifier();
    }
}
