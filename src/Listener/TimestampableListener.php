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
use App\Traits\ArrayTrait;
use App\Traits\DisableListenerTrait;
use App\Utils\DateUtils;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener to update timestampable entities.
 */
#[AsDoctrineListener(Events::onFlush)]
class TimestampableListener implements DisableListenerInterface
{
    use ArrayTrait;
    use DisableListenerTrait;

    private readonly string $emptyUser;

    public function __construct(private readonly Security $security, TranslatorInterface $translator)
    {
        $this->emptyUser = $translator->trans('common.entity_empty_user');
    }

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

        $user = $this->getUser();
        $date = DateUtils::createDateTime();
        foreach ($entities as $entity) {
            if ($entity->updateTimestampable($date, $user)) {
                $this->persist($em, $unitOfWork, $entity);
            }
        }
    }

    /**
     * @return TimestampableInterface[]
     */
    private function filterEntities(array $entities, bool $includeChildren = true): array
    {
        return $this->getUniqueFiltered(\array_map(
            fn (object $entity): ?TimestampableInterface => $this->getTimestampable($entity, $includeChildren),
            $entities
        ));
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
        ]);
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
            return $entity->getParentEntity();
        }

        return null;
    }

    private function getUser(): string
    {
        return $this->security->getUser()?->getUserIdentifier() ?? $this->emptyUser;
    }

    private function persist(EntityManagerInterface $em, UnitOfWork $unitOfWork, TimestampableInterface $entity): void
    {
        $em->persist($entity);
        $metadata = $em->getClassMetadata($entity::class);
        $unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
    }
}
