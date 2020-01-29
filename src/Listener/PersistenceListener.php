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
use App\Traits\TranslatorFlashMessageTrait;
use App\Utils\Utils;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Entity modifications listener.
 *
 * @author Laurent Muller
 */
class PersistenceListener implements EventSubscriber
{
    use TranslatorFlashMessageTrait;

    /**
     * The message title.
     */
    private const TITLE = 'Debug';

    /**
     * The application name.
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
     * The debug mode.
     *
     * @var bool
     */
    private $debug;

    /**
     * Constructor.
     */
    public function __construct(ContainerInterface $container, KernelInterface $kernel, SessionInterface $session, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->translator = $translator;
        $this->debug = $kernel->isDebug();
        $this->appName = $container->getParameter('app_name');
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        if ($this->debug) {
            return [
                Events::postUpdate,
                Events::postPersist,
                Events::postRemove,
            ];
        } else {
            return [];
        }
    }

    /**
     * Handles the post persist event.
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        if ($entity = $this->getEntity($args)) {
            $id = $this->getId($entity, '.add.success');
            $params = $this->getParameters($entity);
            $this->info($this->translateMessage($id, $params));
        }
    }

    /**
     * Handles the post remove event.
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        if ($entity = $this->getEntity($args)) {
            $id = $this->getId($entity, '.delete.success');
            $params = $this->getParameters($entity);
            $this->warning($this->translateMessage($id, $params));
        }
    }

    /**
     * Handles the post update event.
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        if ($entity = $this->getEntity($args)) {
            // special case for user entity when last login change
            if ($entity instanceof User && $this->isLastLogin($args, $entity)) {
                $id = 'security.login.success';
                $params = [
                    '%username%' => $entity->getUsername(),
                    '%appname%' => $this->appName,
                ];
                $domain = 'FOSUserBundle';
            } else {
                $id = $this->getId($entity, '.edit.success');
                $params = $this->getParameters($entity);
                $domain = null;
            }
            $this->info($this->translateMessage($id, $params, $domain));
        }
    }

    /**
     * Gets the entity from the given arguments.
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
     * Gets the message identifier to translate.
     *
     * @param IEntity $entity the entity
     * @param string  $suffix the message suffix
     *
     * @return string the message identifier to translate
     */
    private function getId(IEntity $entity, string $suffix): string
    {
        $name = \strtolower(Utils::getShortName($entity));

        return $name . $suffix;
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
     * Gets the flashbag message title.
     */
    private function getTitle(): string
    {
        return self::TITLE . ' - ' . $this->appName . '|';
    }

    /**
     * Checks if the last login field is updated.
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

    /**
     * Translates the given message and add the title as prefix.
     *
     * @param string      $id         the message id
     * @param array       $parameters an array of parameters for the message
     * @param string|null $domain     the domain for the message or null to use the default
     *
     * @return string the translated string
     */
    private function translateMessage(string $id, array $parameters = [], ?string $domain = null): string
    {
        //$title = $this->getTitle();
        $message = $this->trans($id, $parameters, $domain);

        return self::TITLE . '|' . $message;
    }
}
