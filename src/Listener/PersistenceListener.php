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

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\GlobalMargin;
use App\Entity\IEntity;
use App\Entity\Product;
use App\Entity\User;
use App\Interfaces\IFlashMessageInterface;
use App\Traits\TranslatorFlashMessageTrait;
use App\Utils\Utils;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Entity modifications listener.
 *
 * @author Laurent Muller
 */
class PersistenceListener implements IFlashMessageInterface, EventSubscriber
{
    use TranslatorFlashMessageTrait;

    /**
     * The session key to override the displayed message domain.
     */
    public const LAST_DOMAIN = 'lastDomain';

    /**
     * The session key to override the displayed message.
     */
    public const LAST_MESSAGE = 'lastMessage';

    /**
     * The application name,.
     *
     * @var string
     */
    private $appName;

    /**
     * The entity class names to listen for.
     *
     * @var array
     */
    private static $CLASS_NAMES = [
        Calculation::class,
        CalculationState::class,
        Category::class,
        Customer::class,
        GlobalMargin::class,
        Product::class,
        User::class,
    ];

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
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            //Events::preUpdate,
            Events::postUpdate,
            Events::postPersist,
            // Events::postRemove,
        ];
    }

    /**
     * Handles the post persist event.
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        if ($entity = $this->getEntity($args)) {
            $message = $this->getMessage($entity, '.add.success');
            $params = $this->getParameters($entity);
            $domain = $this->getDomain();
            $this->succesTrans($message, $params, $domain);
        }
    }

    /**
     * Handles the post remove event.
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        if ($entity = $this->getEntity($args)) {
            $message = $this->getMessage($entity, '.delete.success');
            $params = $this->getParameters($entity);
            $domain = $this->getDomain();
            $this->warningTrans($message, $params, $domain);
        }
    }

    /**
     * Handles the post update event.
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if ($entity instanceof User) {
            $manager = $args->getEntityManager();
            $unitOfWork = $manager->getUnitOfWork();
            $changeSet = $unitOfWork->getEntityChangeSet($entity);
            if (\array_key_exists('lastLogin', $changeSet)) {
                $message = 'security.login.success';
                $params = [
                    '%username%' => $entity->getUsername(),
                    '%appname%' => $this->appName,
                ];
                $this->succesTrans($message, $params, 'FOSUserBundle');
            }
        }

//         if ($entity = $this->getEntity($args)) {
//             // special case for user entity when last login change
//             if ($entity instanceof User && $this->isLastLogin($args, $entity)) {
//                 $message = 'security.login.success';
//                 $params = [
//                     '%username%' => $entity->getUsername(),
//                     '%appname%' => $this->appName,
//                 ];
//                 $domain = 'FOSUserBundle';
//             } else {
//                 $message = $this->getMessage($entity, '.edit.success');
//                 $params = $this->getParameters($entity);
//                 $domain = $this->getDomain();
//             }
//             $this->succesTrans($message, $params, $domain);
//         }
    }

    /**
     * Gets the domain used to translate the message.
     *
     * @param string $domain the default domain
     *
     * @return string te domain
     */
    private function getDomain(?string $domain = null): ?string
    {
        if ($this->session->has(self::LAST_DOMAIN)) {
            // $domain = $this->session->get(self::LAST_DOMAIN, $domain);
            // $this->session->remove(self::LAST_DOMAIN);
            $domain = $this->session->remove(self::LAST_DOMAIN);
        }

        return $domain;
    }

    /**
     * Gets the entity from th given arguments.
     *
     * @param LifecycleEventArgs $args the arguments to get entity for
     *
     * @return IEntity|null the entity, if found; null otherwise
     */
    private function getEntity(LifecycleEventArgs $args): ?IEntity
    {
        $entity = $args->getObject();
        if (\in_array(\get_class($entity), self::$CLASS_NAMES, true)) {
            return $entity;
        }

        return null;
    }

    /**
     * Gets the message to translate.
     *
     * @param IEntity $entity the entity
     * @param string  $suffix the message suffix
     *
     * @return string the message to translate
     */
    private function getMessage(IEntity $entity, string $suffix): string
    {
        // saved message?
        if ($this->session->has(self::LAST_MESSAGE)) {
            // $message = $this->session->get(self::LAST_MESSAGE);
            // $this->session->remove(self::LAST_MESSAGE);
            $message = $this->session->remove(self::LAST_MESSAGE);
        } else {
            $message = \strtolower(Utils::getShortName($entity)) . $suffix;
        }

        return $message;
    }

    /**
     * Gets the message parameters.
     *
     * @param IEntity $entity the entity
     *
     * @return array the message parameters
     */
    private function getParameters(IEntity $entity): array
    {
        return ['%name%' => $entity->getDisplay()];
    }

    /**
     * CHecks if the last login field is updated.
     *
     * @param LifecycleEventArgs $args the post update arguments
     * @param User               $user the user entity
     *
     * @return bool true if updated
     */
    private function isLastLogin(LifecycleEventArgs $args, User $user): bool
    {
        $manager = $args->getEntityManager();
        $unitOfWork = $manager->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($user);

        return \array_key_exists('lastLogin', $changeSet);
    }
}
