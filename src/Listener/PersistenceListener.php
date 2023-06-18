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
use App\Utils\StringUtils;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Listener to add flash messages when entities are created, updated or deleted.
 */
#[AsDoctrineListener(Events::postPersist)]
#[AsDoctrineListener(Events::postRemove)]
#[AsDoctrineListener(Events::postUpdate)]
class PersistenceListener implements DisableListenerInterface, ServiceSubscriberInterface
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

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $this->getEntity($args);
        if (!$entity instanceof AbstractEntity) {
            return;
        }

        $id = $this->getId($entity, '.add.success', 'common.add_success');
        $params = $this->getParameters($entity);
        $this->successTrans($id, $params);
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $this->getEntity($args);
        if (!$entity instanceof AbstractEntity) {
            return;
        }

        $id = $this->getId($entity, '.delete.success', 'common.delete_success');
        $params = $this->getParameters($entity);
        $this->warningTrans($id, $params);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $this->getEntity($args);
        if (!$entity instanceof AbstractEntity) {
            return;
        }
        if ($this->isLastLogin($args, $entity)) {
            return;
        }

        $id = $this->getId($entity, '.edit.success', 'common.edit_success');
        $params = $this->getParameters($entity);
        $this->successTrans($id, $params);
    }

    /**
     * @phpstan-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
     */
    private function getEntity(LifecycleEventArgs $args): ?AbstractEntity
    {
        if (!$this->isEnabled()) {
            return null;
        }

        /** @var AbstractEntity $entity */
        $entity = $args->getObject();
        if (\in_array($entity::class, self::CLASS_NAMES, true)) {
            return $entity;
        }

        return null;
    }

    private function getId(AbstractEntity $entity, string $suffix, string $default): string
    {
        $id = \strtolower(StringUtils::getShortName($entity)) . $suffix;

        return $this->isTransDefined($id) ? $id : $default;
    }

    private function getParameters(AbstractEntity $entity): array
    {
        return ['%name%' => $entity->getDisplay()];
    }

    /**
     * @phpstan-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
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
