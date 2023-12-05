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
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
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

    private const USER_LOGIN = [
        'lastLogin',
    ];

    private const USER_PASSWORD = [
        'password',
    ];

    private const USER_RESET = [
        'expiresAt',
        'selector',
        'hashedToken',
        'requestedAt',
    ];

    private const USER_RIGHTS = [
        'rights',
        'overwrite',
    ];

    /**
     * @psalm-api
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $uow = $args->getObjectManager()->getUnitOfWork();
        $updates = $this->filterEntities($uow->getScheduledEntityUpdates());
        $deletions = $this->filterEntities($uow->getScheduledEntityDeletions());
        $insertions = $this->filterEntities($uow->getScheduledEntityInsertions());

        $collections = $this->filterCollections($uow->getScheduledCollectionUpdates());
        if ([] !== $collections) {
            $updates = $this->getUniqueMerged($updates, $collections);
        }
        $updates = \array_diff($updates, $insertions);

        $collections = $this->filterCollections($uow->getScheduledCollectionDeletions());
        if ([] !== $collections) {
            $deletions = $this->getUniqueMerged($deletions, $collections);
        }

        $this->notifyEntities($uow, $updates, '.edit.success', 'common.edit_success');
        $this->notifyEntities($uow, $deletions, '.delete.success', 'common.delete_success', FlashType::WARNING);
        $this->notifyEntities($uow, $insertions, '.add.success', 'common.add_success');
    }

    /**
     * @psalm-param Collection<array-key, object>[] $collections
     *
     * @psalm-return EntityInterface[]
     */
    private function filterCollections(array $collections): array
    {
        if ([] === $collections) {
            return [];
        }

        $result = [];
        foreach ($collections as $collection) {
            $entities = $this->filterEntities($collection, true);
            if ([] !== $entities) {
                $result = $this->getUniqueMerged($result, $entities);
            }
        }

        return $result;
    }

    /**
     * @psalm-param array|Collection<array-key, object> $entities
     *
     * @psalm-return EntityInterface[]
     */
    private function filterEntities(array|Collection $entities, bool $includeChildren = false): array
    {
        if ($entities instanceof Collection) {
            $entities = $entities->toArray();
        }

        return $this->getUniqueFiltered(\array_map(
            fn (object $entity): ?EntityInterface => $this->filterEntity($entity, $includeChildren),
            $entities
        ));
    }

    private function filterEntity(?object $entity, bool $includeChildren = false): ?EntityInterface
    {
        if ($entity instanceof EntityInterface && \in_array($entity::class, self::CLASS_NAMES, true)) {
            return $entity;
        }

        if ($includeChildren && $entity instanceof ParentEntityInterface) {
            return $this->filterEntity($entity->getParentEntity());
        }

        return null;
    }

    private function getId(EntityInterface $entity, string $suffix, string $default): string
    {
        $id = \strtolower(StringUtils::getShortName($entity)) . $suffix;

        return $this->isTransDefined($id) ? $id : $default;
    }

    private function isMatchFields(array $changeSet, array $fields): bool
    {
        return [] !== \array_intersect($changeSet, $fields);
    }

    private function isUserLogin(array $changeSet): bool
    {
        return $this->isMatchFields($changeSet, self::USER_LOGIN);
    }

    private function isUserPassword(array $changeSet): bool
    {
        return $this->isMatchFields($changeSet, self::USER_PASSWORD);
    }

    private function isUserReset(array $changeSet): bool
    {
        return $this->isMatchFields($changeSet, self::USER_RESET);
    }

    private function isUserRights(array $changeSet): bool
    {
        return $this->isMatchFields($changeSet, self::USER_RIGHTS);
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
        UnitOfWork $uow,
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
                $changeSet = \array_keys($uow->getEntityChangeSet($entity));
                if ($this->isUserLogin($changeSet) || $this->isUserReset($changeSet)) {
                    continue;
                } elseif ($this->isUserRights($changeSet)) {
                    $this->notify($entity, '', 'user.rights.success');
                    continue;
                } elseif ($this->isUserPassword($changeSet)) {
                    $this->notify($entity, '', 'user.change_password.change_success');
                    continue;
                }
            }
            $this->notify($entity, $suffix, $default, $type);
        }
    }
}
