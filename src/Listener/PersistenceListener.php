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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Listener to add flash messages when entities are created, updated or deleted.
 */
#[AsDoctrineListener(Events::onFlush)]
class PersistenceListener implements DisableListenerInterface, ServiceSubscriberInterface
{
    use ArrayTrait;
    use DisableListenerTrait;
    use ServiceMethodsSubscriberTrait;
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
        'selector',
        'expiresAt',
        'requestedAt',
        'hashedToken',
    ];

    private const USER_RIGHTS = [
        'rights',
        'overwrite',
    ];

    public function __construct(private readonly Security $security)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        // get modifications
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();
        $updates = $this->filterEntities($unitOfWork->getScheduledEntityUpdates(), true);
        $deletions = $this->filterEntities($unitOfWork->getScheduledEntityDeletions());
        $insertions = $this->filterEntities($unitOfWork->getScheduledEntityInsertions());

        // merge updated and insertions
        $collections = $this->filterCollections($unitOfWork->getScheduledCollectionUpdates());
        if ([] !== $collections) {
            $updates = $this->getUniqueMerged($updates, $collections);
        }
        $updates = \array_diff($updates, $insertions);

        // merge deleted
        $collections = $this->filterCollections($unitOfWork->getScheduledCollectionDeletions());
        if ([] !== $collections) {
            $deletions = $this->getUniqueMerged($deletions, $collections);
        }

        // notify
        $this->notifyEntities($unitOfWork, $updates, '.edit.success', 'common.edit_success');
        $this->notifyEntities($unitOfWork, $deletions, '.delete.success', 'common.delete_success', FlashType::WARNING);
        $this->notifyEntities($unitOfWork, $insertions, '.add.success', 'common.add_success');
    }

    /**
     * @phpstan-param Collection<array-key, object>[] $collections
     *
     * @phpstan-return EntityInterface[]
     */
    private function filterCollections(array $collections): array
    {
        if ([] === $collections) {
            return [];
        }

        $result = [];
        foreach ($collections as $collection) {
            $result = $this->getUniqueMerged($result, $this->filterEntities($collection, true));
        }

        return $result;
    }

    /**
     * @phpstan-param array|Collection<array-key, object> $entities
     *
     * @phpstan-return EntityInterface[]
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

    private function filterEntity(?object $entity, bool $includeChildren): ?EntityInterface
    {
        if ($entity instanceof EntityInterface && \in_array($entity::class, self::CLASS_NAMES, true)) {
            return $entity;
        }

        if ($includeChildren && $entity instanceof ParentEntityInterface) {
            return $this->filterEntity($entity->getParentEntity(), true);
        }

        return null;
    }

    private function getId(EntityInterface $entity, string $suffix, string $default): string
    {
        $id = \strtolower(StringUtils::getShortName($entity)) . $suffix;

        return $this->isTransDefined($id) ? $id : $default;
    }

    private function handleUserChangeSet(UnitOfWork $unitOfWork, User $user): bool
    {
        if ($this->isCurrentUser($user)) {
            return true;
        }

        $changeSet = \array_keys($unitOfWork->getEntityChangeSet($user));
        if ($this->isUserLogin($changeSet) || $this->isUserReset($changeSet)) {
            return true;
        }

        if ($this->isUserRights($changeSet)) {
            $this->notifyEntity($user, '', 'user.rights.success');

            return true;
        }

        if ($this->isUserPassword($changeSet)) {
            $this->notifyEntity($user, '', 'user.change_password.change_success');

            return true;
        }

        return false;
    }

    private function isCurrentUser(User $user): bool
    {
        return $user === $this->security->getUser();
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

    /**
     * @param EntityInterface[] $entities
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
            if (User::class === $entity::class && $this->handleUserChangeSet($unitOfWork, $entity)) {
                continue;
            }
            $this->notifyEntity($entity, $suffix, $default, $type);
        }
    }

    private function notifyEntity(
        EntityInterface $entity,
        string $suffix,
        string $default,
        FlashType $type = FlashType::SUCCESS
    ): void {
        $id = '' === $suffix ? $default : $this->getId($entity, $suffix, $default);
        $message = $this->trans($id, ['%name%' => $entity->getDisplay()]);
        $this->addFlashMessage($type, $message);
    }
}
