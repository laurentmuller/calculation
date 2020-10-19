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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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

    /**
     * The application name.
     *
     * @var string
     */
    private $appName;

    private UserRepository $repository;

    /**
     * Constructor.
     */
    public function __construct(UserRepository $repository, TranslatorInterface $translator, string $appName)
    {
        $this->repository = $repository;
        $this->translator = $translator;
        $this->appName = $appName;
    }

    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    /**
     * Handles the interactive login event.
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $token = $event->getAuthenticationToken();
        $this->session = $event->getRequest()->getSession();
        if ($this->updateUser($token) && null !== $this->session) {
            $params = [
                '%username%' => $token->getUsername(),
                '%appname%' => $this->appName,
            ];
            $this->succesTrans('security.login.success', $params);
        }
    }

    /**
     * Update the last login date, if applicable, of the user.
     *
     * @param TokenInterface $token the token to get user from
     *
     * @return bool true if updated
     */
    private function updateUser(TokenInterface $token): bool
    {
        if ($token instanceof RememberMeToken || $token instanceof PostAuthenticationGuardToken) {
            $user = $token->getUser();
            if ($user instanceof User) {
                return $this->repository->updateLastLogin($user);
            }
        }

        return false;
    }
}
