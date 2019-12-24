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

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Listener;

use App\Entity\User;
use App\Interfaces\IFlashMessageInterface;
use App\Traits\TranslatorFlashMessageTrait;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class LastLoginListener implements EventSubscriberInterface, IFlashMessageInterface
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
     *
     * @param SessionInterface    $session    the session
     * @param TranslatorInterface $translator the translator
     * @param string              $app_name   the application name
     */
    public function __construct(SessionInterface $session, TranslatorInterface $translator, string $app_name)
    {
        $this->session = $session;
        $this->translator = $translator;
        $this->appName = $app_name;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FOSUserEvents::SECURITY_IMPLICIT_LOGIN => 'onImplicitLogin',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        ];
    }

    public function onImplicitLogin(UserEvent $event): void
    {
        $user = $event->getUser();
        if ($user instanceof User) {
            $this->loginSuccess($user);
        }
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($user instanceof User) {
            $this->loginSuccess($user);
        }
    }

    private function loginSuccess(User $user): void
    {
        $params = [
            '%username%' => $user->getUsername(),
            '%appname%' => $this->appName,
        ];
        $this->succesTrans('security.login.success', $params, 'FOSUserBundle');
    }
}
