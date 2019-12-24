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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * User logout listener.
 *
 * @internal
 */
final class UserLogoutListener extends AbstractListener implements LogoutHandlerInterface
{
    /**
     * Constructor.
     *
     * @param ContainerInterface  $container  the container service
     * @param SessionInterface    $session    the session service
     * @param TranslatorInterface $translator the translator service
     */
    public function __construct(ContainerInterface $container, SessionInterface $session, TranslatorInterface $translator)
    {
        parent::__construct($container, $session, $translator);
    }

    /**
     * {@inheritdoc}
     */
    public function logout(Request $request, Response $response, TokenInterface $token): void
    {
        $appName = $this->container->getParameter('app_name');
        $this->succesTrans('security.logout.success', ['%appname%' => $appName], 'FOSUserBundle');
    }
}
