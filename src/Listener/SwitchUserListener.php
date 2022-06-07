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

use App\Traits\TranslatorFlashMessageTrait;
use App\Util\Utils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener for the switch user event.
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
        // session?
        $request = $event->getRequest();
        if (!$this->setSessionFromRequest($request)) {
            return;
        }

        // get values
        /** @psalm-var string $action */
        $action = Utils::getRequestInputBag($request)->get(self::SWITCH_USER);
        $original = $this->getOriginalUsername($event);
        $name = $this->getTargetUsername($event);

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
