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

use App\Entity\AbstractEntity;
use App\Entity\Calculation;
use App\Interfaces\ParentCalculationInterface;
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
     */
    protected function getEntities(UnitOfWork $unitOfWork): array
    {
        /** @var AbstractEntity[] $entities */
        $entities = [
            ...$unitOfWork->getScheduledEntityUpdates(),
            ...$unitOfWork->getScheduledEntityDeletions(),
            ...$unitOfWork->getScheduledEntityInsertions(),
        ];

        if ([] === $entities) {
            return [];
        }

        $result = [];
        foreach ($entities as $entity) {
            if (null !== $calculation = $this->getParentCalculation($entity)) {
                $result[(int) $calculation->getId()] = $calculation;
            }
        }

        if ([] === $result) {
            return $result;
        }

        // exclude deleted and inserted calculations
        /** @var array $entities */
        $entities = [
            ...$unitOfWork->getScheduledEntityDeletions(),
            ...$unitOfWork->getScheduledEntityInsertions(),
        ];
        if ([] === $entities) {
            return $result;
        }
        $excluded = $this->getCalculations($entities);
        if ([] === $excluded) {
            return $result;
        }

        return \array_diff($result, $excluded);
    }

    private function getCalculations(array $entities): array
    {
        return \array_filter($entities, static function (AbstractEntity $entity): bool {
            return $entity instanceof Calculation;
        });
    }

    private function getParentCalculation(AbstractEntity $entity): ?Calculation
    {
        if ($entity instanceof ParentCalculationInterface) {
            return $entity->getCalculation();
        }

        return null;
    }
}
