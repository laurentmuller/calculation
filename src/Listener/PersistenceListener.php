<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Listener;

use App\Entity\AbstractEntity;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\GlobalMargin;
use App\Entity\Group;
use App\Entity\Product;
use App\Entity\Task;
use App\Entity\User;
use App\Interfaces\DisableListenerInterface;
use App\Traits\DisableListenerTrait;
use App\Traits\TranslatorFlashMessageAwareTrait;
use App\Util\Utils;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Listener to display flash messages when entities are updated.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PersistenceListener implements DisableListenerInterface, EventSubscriber, ServiceSubscriberInterface
{
    use DisableListenerTrait;
    use ServiceSubscriberTrait;
    use TranslatorFlashMessageAwareTrait;

    /**
     * The entity class names to listen for.
     */
    private const CLASS_NAMES = [
        Calculation::class,
        CalculationState::class,
        Category::class,
        Customer::class,
        GlobalMargin::class,
        Group::class,
        Product::class,
        Task::class,
        User::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postRemove,
            Events::postUpdate,
        ];
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        if (null !== $entity = $this->getEntity($args)) {
            $id = $this->getId($entity, '.add.success', 'common.add_success');
            $params = $this->getParameters($entity);
            $this->successTrans($id, $params);
        }
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        if (null !== $entity = $this->getEntity($args)) {
            $id = $this->getId($entity, '.delete.success', 'common.delete_success');
            $params = $this->getParameters($entity);
            $this->warningTrans($id, $params);
        }
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        if (null !== ($entity = $this->getEntity($args)) && !$this->isLastLogin($args, $entity)) {
            $id = $this->getId($entity, '.edit.success', 'common.edit_success');
            $params = $this->getParameters($entity);
            $this->successTrans($id, $params);
        }
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    private function getEntity(LifecycleEventArgs $args): ?AbstractEntity
    {
        /** @var AbstractEntity $entity */
        $entity = $args->getObject();
        if (\in_array($entity::class, self::CLASS_NAMES, true)) {
            return $entity;
        }

        return null;
    }

    private function getId(AbstractEntity $entity, string $suffix, string $default): string
    {
        $id = \strtolower(Utils::getShortName($entity)) . $suffix;

        return $this->isTransDefined($id) ? $id : $default;
    }

    private function getParameters(AbstractEntity $entity): array
    {
        return ['%name%' => $entity->getDisplay()];
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    private function isLastLogin(LifecycleEventArgs $args, AbstractEntity $entity): bool
    {
        if ($entity instanceof User) {
            $manager = $args->getObjectManager();
            $unitOfWork = $manager->getUnitOfWork();
            $changeSet = $unitOfWork->getEntityChangeSet($entity);

            return \array_key_exists('lastLogin', $changeSet);
        }

        return false;
    }
}
