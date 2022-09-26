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

use App\Interfaces\DisableListenerInterface;
use App\Interfaces\ParentTimestampableInterface;
use App\Interfaces\TimestampableInterface;
use App\Traits\DisableListenerTrait;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener to update timestampable entities.
 *
 * @see TimestampableInterface
 * @see ParentTimestampableInterface
 */
class TimestampableListener implements DisableListenerInterface
{
    use DisableListenerTrait;

    private readonly string $emptyUser;

    /**
     * Constructor.
     */
    public function __construct(private readonly Security $security, TranslatorInterface $translator)
    {
        $this->emptyUser = $translator->trans('common.empty_user');
    }

    /**
     * Handles the flush event.
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $em = $args->getObjectManager();
        $unitOfWork = $em->getUnitOfWork();
        $entities = $this->getEntities($unitOfWork);
        if ([] === $entities) {
            return;
        }

        $user = $this->getUserName();
        $date = new \DateTimeImmutable();
        foreach ($entities as $entity) {
            if ($this->updateEntity($entity, $user, $date)) {
                $em->persist($entity);
                $metadata = $em->getClassMetadata($entity::class);
                $unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
            }
        }
    }

    /**
     * @return TimestampableInterface[]
     */
    private function getEntities(UnitOfWork $unitOfWork): array
    {
        /** @var object[] $entities */
        $entities = [
            ...$unitOfWork->getScheduledEntityUpdates(),
            ...$unitOfWork->getScheduledEntityDeletions(),
            ...$unitOfWork->getScheduledEntityInsertions(),
        ];
        if ([] === $entities) {
            return [];
        }

        // get all entities include from ParentTimestampableInterface
        $result = [];
        foreach ($entities as $entity) {
            if (null !== $timestampable = $this->getTimestampable($entity, true)) {
                $result[(int) $timestampable->getId()] = $timestampable;
            }
        }
        if ([] === $result) {
            return [];
        }

        // exclude deleted TimestampableInterface
        $entities = $unitOfWork->getScheduledEntityDeletions();
        foreach ($entities as $entity) {
            if (null !== $timestampable = $this->getTimestampable($entity, false)) {
                unset($result[(int) $timestampable->getId()]);
            }
        }

        return $result;
    }

    private function getTimestampable(object $entity, bool $includeChildren): ?TimestampableInterface
    {
        if ($entity instanceof TimestampableInterface) {
            return $entity;
        }

        if ($includeChildren && $entity instanceof ParentTimestampableInterface) {
            return $entity->getParentTimestampable();
        }

        return null;
    }

    private function getUserName(): string
    {
        if (null !== ($user = $this->security->getUser())) {
            return $user->getUserIdentifier();
        }

        return $this->emptyUser;
    }

    private function updateEntity(TimestampableInterface $entity, string $user, \DateTimeImmutable $date): bool
    {
        $changed = false;
        if (null === $entity->getCreatedAt()) {
            $entity->setCreatedAt($date);
            $changed = true;
        }
        if (null === $entity->getCreatedBy()) {
            $entity->setCreatedBy($user);
            $changed = true;
        }
        if ($date !== $entity->getUpdatedAt()) {
            $entity->setUpdatedAt($date);
            $changed = true;
        }
        if ($user !== $entity->getUpdatedBy()) {
            $entity->setUpdatedBy($user);
            $changed = true;
        }

        return $changed;
    }
}
