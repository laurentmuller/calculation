<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Listener;

use App\Entity\Calculation;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Interfaces\DisableListenerInterface;
use App\Traits\DisableListenerTrait;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener to update calculation when groups or items are modified.
 *
 * @author Laurent Muller
 *
 * @internal
 */
final class CalculationListener implements DisableListenerInterface
{
    use DisableListenerTrait;

    private Security $security;

    private string $username;

    /**
     * Constructor.
     */
    public function __construct(Security $security, TranslatorInterface $translator)
    {
        $this->security = $security;
        $this->username = $translator->trans('common.empty_user');
    }

    /**
     * Handles the flush event.
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        // enabled?
        if (!$this->enabled) {
            return;
        }

        // get entities
        $em = $args->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();
        $entities = \array_merge($unitOfWork->getScheduledEntityInsertions(), $unitOfWork->getScheduledEntityUpdates(), $unitOfWork->getScheduledCollectionDeletions());
        if (empty($entities)) {
            return;
        }

        // get calculations
        /** @var \App\Entity\Calculation[] $calculations */
        $calculations = $this->getCalculations($entities);
        if (empty($calculations)) {
            return;
        }

        $user = $this->getUserName();
        $date = new \DateTimeImmutable();
        $metadata = $em->getClassMetadata(Calculation::class);

        foreach ($calculations as $calculation) {
            // update
            $calculation->setUpdated($date, $user);
            $em->persist($calculation);

            // recompute
            $unitOfWork->recomputeSingleEntityChangeSet($metadata, $calculation);
        }
    }

    /**
     * Filters the entities and returns the calculations to update.
     *
     * @param array $entities the modified entities
     *
     * @return \App\Entity\Calculation[] the calculations to update (can be empty)
     */
    private function getCalculations(array $entities): array
    {
        $result = [];

        // filter
        foreach ($entities as $entity) {
            // calculation is already updated
            if ($entity instanceof Calculation) {
                continue;
            }

            if ($entity instanceof CalculationItem) {
                $entity = $entity->getCalculation();
            } elseif ($entity instanceof CalculationGroup) {
                $entity = $entity->getCalculation();
            }

            // calculation?
            if ($entity instanceof Calculation) {
                $result[(int) $entity->getId()] = $entity;
            }
        }

        return $result;
    }

    /**
     * Gets the user name.
     */
    private function getUserName(): string
    {
        if ($user = $this->security->getUser()) {
            return $user->getUserIdentifier();
        }

        // default user
        return $this->username;
    }
}
