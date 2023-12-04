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

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\GlobalMargin;
use App\Entity\Group;
use App\Entity\Product;
use App\Entity\Task;
use App\Entity\User;
use App\Enums\FlashType;
use App\Interfaces\DisableListenerInterface;
use App\Interfaces\EntityInterface;
use App\Interfaces\ParentEntityInterface;
use App\Traits\ArrayTrait;
use App\Traits\DisableListenerTrait;
use App\Traits\TranslatorFlashMessageAwareTrait;
use App\Utils\StringUtils;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Listener to add flash messages when entities are created, updated or deleted.
 */
#[AsDoctrineListener(Events::onFlush)]
class PersistenceListener implements DisableListenerInterface, ServiceSubscriberInterface
{
    use ArrayTrait;
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

    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $manager = $args->getObjectManager();
        $unitOfWork = $manager->getUnitOfWork();
        $updates = $this->filterEntities($unitOfWork->getScheduledEntityUpdates());
        $deletions = $this->filterEntities($unitOfWork->getScheduledEntityDeletions());
        $insertions = $this->filterEntities($unitOfWork->getScheduledEntityInsertions());

        $collectionUpdates = $this->filterCollections($unitOfWork->getScheduledCollectionUpdates());
        if ([] !== $collectionUpdates) {
            $updates = $this->getUniqueMerged($updates, $collectionUpdates);
        }
        $updates = \array_diff($updates, $insertions);

        $collectionDeletions = $this->filterCollections($unitOfWork->getScheduledCollectionDeletions());
        if ([] !== $collectionDeletions) {
            $deletions = $this->getUniqueMerged($deletions, $collectionDeletions);
        }

        $this->notifyEntities($unitOfWork, $updates, '.edit.success', 'common.edit_success');
        $this->notifyEntities($unitOfWork, $deletions, '.delete.success', 'common.delete_success', FlashType::WARNING);
        $this->notifyEntities($unitOfWork, $insertions, '.add.success', 'common.add_success');
    }

    /**
     * @psalm-param array<int, PersistentCollection<array-key, object>> $collections
     *
     * @psalm-return EntityInterface[]
     */
    private function filterCollections(array $collections): array
    {
        $result = [];
        foreach ($collections as $collection) {
            $entities = $this->filterEntities($collection);
            if ([] !== $entities) {
                $result = $this->getUniqueMerged($result, $entities);
            }
        }

        return $result;
    }

    /**
     * @psalm-param array|PersistentCollection<array-key, object> $entities
     *
     * @psalm-return EntityInterface[]
     */
    private function filterEntities(array|PersistentCollection $entities): array
    {
        if ($entities instanceof PersistentCollection) {
            $entities = $entities->toArray();
        }

        return $this->getUniqueFiltered(\array_map(
            fn (object $entity): ?EntityInterface => $this->filterEntity($entity),
            $entities
        ));
    }

    private function filterEntity(?object $entity): ?EntityInterface
    {
        if ($entity instanceof EntityInterface && \in_array($entity::class, self::CLASS_NAMES, true)) {
            return $entity;
        }

        if ($entity instanceof ParentEntityInterface) {
            return $this->filterEntity($entity->getParentEntity());
        }

        return null;
    }

    private function getId(EntityInterface $entity, string $suffix, string $default): string
    {
        $id = \strtolower(StringUtils::getShortName($entity)) . $suffix;

        return $this->isTransDefined($id) ? $id : $default;
    }

    private function isObjectChanged(array $changeSet, string ...$fields): bool
    {
        return [] !== \array_intersect($changeSet, $fields);
    }

    private function isUserLogin(array $changeSet): bool
    {
        return $this->isObjectChanged($changeSet, 'lastLogin');
    }

    private function isUserPassword(array $changeSet): bool
    {
        return $this->isObjectChanged($changeSet, 'password');
    }

    private function isUserRights(array $changeSet): bool
    {
        return $this->isObjectChanged($changeSet, 'rights', 'overwrite');
    }

    private function notify(
        EntityInterface $entity,
        string $suffix,
        string $default,
        FlashType $type = FlashType::SUCCESS
    ): void {
        $id = '' === $suffix ? $default : $this->getId($entity, $suffix, $default);
        $message = $this->trans($id, ['%name%' => $entity->getDisplay()]);
        $this->addFlashMessage($type, $message);
    }

    /**
     * @psalm-param EntityInterface[] $entities
     */
    private function notifyEntities(
        UnitOfWork $unitOfWork,
        array $entities,
        string $suffix,
        string $default,
        FlashType $type = FlashType::SUCCESS
    ): void {
        if ([] === $entities) {
            return;
        }
        foreach ($entities as $entity) {
            if (User::class === $entity::class) {
                $changeSet = \array_keys($unitOfWork->getEntityChangeSet($entity));
                if ($this->isUserRights($changeSet)) {
                    $this->notify($entity, '', 'user.rights.success');
                    continue;
                } elseif ($this->isUserPassword($changeSet)) {
                    $this->notify($entity, '', 'user.change_password.change_success');
                    continue;
                } elseif ($this->isUserLogin($changeSet)) {
                    continue;
                }
            }
            $this->notify($entity, $suffix, $default, $type);
        }
    }
}
