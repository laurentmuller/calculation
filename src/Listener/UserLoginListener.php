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
use App\Interfaces\IFlashMessageInterface;
use App\Traits\TranslatorFlashMessageTrait;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * User login listener.
 *
 * @author Laurent Muller
 */
class UserLoginListener implements IFlashMessageInterface
{
    use TranslatorFlashMessageTrait;

    /**
     * The application name,.
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
     * Handle the entity post update event.
     *
     * @param User               $user  the updated user
     * @param LifecycleEventArgs $event the source event
     */
    public function __invoke(User $user, LifecycleEventArgs $event): void
    {
        $manager = $event->getEntityManager();
        $unitOfWork = $manager->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($user);
        if (\array_key_exists('lastLogin', $changeSet)) {
//             $params = [
//                 '%username%' => $user->getUsername(),
//                 '%appname%' => $this->appName,
//             ];
            // $this->succesTrans('security.login.success', $params, 'FOSUserBundle');
        }
    }
}
