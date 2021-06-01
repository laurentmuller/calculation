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

    private string $appNameVersion;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, string $appNameVersion)
    {
        $this->translator = $translator;
        $this->appNameVersion = $appNameVersion;
    }

    public static function getSubscribedEvents()
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    /**
     * Handles the logout event.
     */
    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->hasSession()) {
            $this->session = $request->getSession();
            if ($username = $this->getUsername($event)) {
                $params = [
                    '%username%' => $username,
                    '%appname%' => $this->appNameVersion,
                ];
                $this->succesTrans('security.logout.success', $params);
            }
        }
    }

    /**
     * Gets the user name from the given event.
     */
    private function getUsername(LogoutEvent $event): ?string
    {
        if ($token = $event->getToken()) {
            return $token->getUserIdentifier();
        }

        return null;
    }
}
