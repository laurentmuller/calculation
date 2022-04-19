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
use App\Interfaces\DisableListenerInterface;
use App\Interfaces\TimestampableInterface;
use App\Traits\DisableListenerTrait;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener to update timestampable entities.
 *
 * @author Laurent Muller
 *
 * @see TimestampableInterface
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
        // enabled?
        if (!$this->isEnabled()) {
            return;
        }

        $em = $args->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();

        // get entities
        $entities = $this->getEntities($unitOfWork);
        if ([] === $entities) {
            return;
        }

        // get update values
        $user = $this->getUserName();
        $date = new \DateTimeImmutable();

        foreach ($entities as $entity) {
            // update?
            if ($this->updateEntity($entity, $user, $date)) {
                // persist
                $em->persist($entity);

                // recompute
                $class_name = $entity::class;
                $metadata = $em->getClassMetadata($class_name);
                $unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
            }
        }
    }

    /**
     * Gets the entities to update.
     *
     * @return TimestampableInterface[]
     */
    protected function getEntities(UnitOfWork $unitOfWork): array
    {
        /** @var AbstractEntity[] $entities */
        $entities = [
            ...$unitOfWork->getScheduledEntityInsertions(),
            ...$unitOfWork->getScheduledEntityUpdates(),
        ];

        if ([] === $entities) {
            return [];
        }

        return \array_filter($entities, static fn (AbstractEntity $entity): bool => $entity instanceof TimestampableInterface);
    }

    /**
     * Gets the connected username.
     */
    private function getUserName(): string
    {
        if (null !== ($user = $this->security->getUser())) {
            return $user->getUserIdentifier();
        }

        return $this->emptyUser;
    }

    /**
     * Update the given entity.
     */
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
