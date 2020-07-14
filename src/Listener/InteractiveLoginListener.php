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

use App\Entity\User;
use App\Repository\UserRepository;
use App\Traits\TranslatorFlashMessageTrait;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener for the user interactive login event.
 *
 * @author Laurent Muller
 */
class InteractiveLoginListener implements EventSubscriberInterface
{
    use TranslatorFlashMessageTrait;

    private ParameterBagInterface $parameters;

    private UserRepository $repository;

    public function __construct(ParameterBagInterface $parameters, SessionInterface $session, UserRepository $repository, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->repository = $repository;
        $this->translator = $translator;
        $this->parameters = $parameters;
    }

    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $token = $event->getAuthenticationToken();
        if ($this->updateUser($token)) {
            $params = [
                '%username%' => $token->getUsername(),
                '%appname%' => $this->getParameter('app_name'),
            ];
            $this->succesTrans('security.login.success', $params);
        }
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

    private function updateUser(TokenInterface $token): bool
    {
        if ($token instanceof RememberMeToken || $token instanceof PostAuthenticationGuardToken) {
            $user = $token->getUser();
            if ($user instanceof User) {
                $this->repository->updateLastLogin($user);

                return true;
            }
        }

        return false;
    }
}
