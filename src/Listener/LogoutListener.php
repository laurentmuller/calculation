<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
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

    /**
     * The application name.
     *
     * @var string
     */
    private $appName;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, string $appName)
    {
        $this->translator = $translator;
        $this->appName = $appName;
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
        $username = $this->getUsername($event);
        $this->session = $event->getRequest()->getSession();
        if (null !== $username && null !== $this->session) {
            $params = [
                '%username%' => $username,
                '%appname%' => $this->appName,
            ];
            $this->succesTrans('security.logout.success', $params);
        }
    }

    /**
     * Gets the user name from the given event.
     */
    private function getUsername(LogoutEvent $event): ?string
    {
        $token = $event->getToken();
        if (null !== $token) {
            return $token->getUsername();
        }

        return null;
    }
}
