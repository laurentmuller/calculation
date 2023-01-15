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
use Symfony\Bundle\SecurityBundle\Security;
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
            if ($entity->updateTimestampable($date, $user)) {
                $em->persist($entity);
                $metadata = $em->getClassMetadata($entity::class);
                $unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
            }
        }
    }

    /**
     * @return TimestampableInterface[]
     */
    private function filterEntities(array $entities, bool $includeChildren): array
    {
        return \array_unique(\array_filter(\array_map(
            fn (object $entity) => $this->getTimestampable($entity, $includeChildren),
            $entities
        )));
    }

    /**
     * @return TimestampableInterface[]
     */
    private function getEntities(UnitOfWork $unitOfWork): array
    {
        $updated = $this->filterEntities([
            ...$unitOfWork->getScheduledEntityUpdates(),
            ...$unitOfWork->getScheduledEntityDeletions(),
            ...$unitOfWork->getScheduledEntityInsertions(),
        ], true);
        if ([] === $updated) {
            return [];
        }

        $deleted = $this->filterEntities($unitOfWork->getScheduledEntityDeletions(), false);
        if ([] === $deleted) {
            return $updated;
        }

        return \array_diff($updated, $deleted);
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
}
