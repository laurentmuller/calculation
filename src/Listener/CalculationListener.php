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
use Doctrine\ORM\UnitOfWork;

/**
 * Listener to update calculations when groups, categories or items are modified.
 *
 * @author Laurent Muller
 */
final class CalculationListener extends TimestampableListener
{
    /**
     * {@inheritDoc}
     *
     * @return Calculation[]
     */
    protected function filterEntities(array $entities): array
    {
        $result = [];

        foreach ($entities as $entity) {
            // calculation is already updated by the parent class
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
     * {@inheritDoc}
     */
    protected function getEntities(UnitOfWork $unitOfWork): array
    {
        return [
            ...$unitOfWork->getScheduledEntityInsertions(),
            ...$unitOfWork->getScheduledEntityDeletions(),
            ...$unitOfWork->getScheduledEntityUpdates(),
        ];
    }
}
