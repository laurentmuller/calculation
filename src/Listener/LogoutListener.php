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
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

    private ParameterBagInterface $parameters;

    public function __construct(ParameterBagInterface $parameters, SessionInterface $session, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->translator = $translator;
        $this->parameters = $parameters;
    }

    public static function getSubscribedEvents()
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $params = ['%appname%' => $this->getParameter('app_name')];
        $this->succesTrans('security.logout.success', $params);
    }

    /**
     * Gets a container parameter.
     *
     * @param string $name the parameter name
     *
     * @return mixed the parameter value
     *
     * @throws ParameterNotFoundException if the parameter is not defined
     */
    private function getParameter(string $name)
    {
        return $this->parameters->get($name);
    }
}
