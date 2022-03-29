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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener for the user logout event.
 *
 * @author Laurent Muller
 */
class LogoutListener implements EventSubscriberInterface
{
    use TranslatorFlashMessageTrait;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, private string $appNameVersion)
    {
        $this->setTranslator($translator);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => 'onLogout'];
    }

    /**
     * Handles the logout event.
     */
    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $username = $this->getUsername($event);
        $this->notify($request, $username);
    }

    /**
     * Gets the username from the given event.
     */
    private function getUsername(LogoutEvent $event): ?string
    {
        if (null !== $token = $event->getToken()) {
            return $token->getUserIdentifier();
        }

        return null;
    }

    /**
     * Notify the success logout of the user.
     */
    private function notify(Request $request, ?string $username): void
    {
        if (null !== $username && $this->setSessionFromRequest($request)) {
            $params = [
                '%username%' => $username,
                '%appname%' => $this->appNameVersion,
            ];
            $this->successTrans('security.logout.success', $params);
        }
    }
}
