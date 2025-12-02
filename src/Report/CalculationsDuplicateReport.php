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

namespace App\Report;

use App\Controller\AbstractController;
use App\Traits\DuplicateItemsTrait;

/**
 * Report for calculations with duplicate items.
 *
 * @phpstan-import-type CalculationItemType from \App\Repository\CalculationRepository
 * @phpstan-import-type CalculationItemEntry from \App\Repository\CalculationRepository
 */
class CalculationsDuplicateReport extends AbstractCalculationItemsReport
{
    use DuplicateItemsTrait;

    /**
     * @phpstan-param CalculationItemType[] $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller, $entities, 'duplicate.title', 'duplicate.description');
    }

    /**
     * @phpstan-param CalculationItemType[] $entities
     */
    #[\Override]
    protected function computeItemsCount(array $entities): int
    {
        return \array_reduce($entities, static function (int $carry, array $entity): int {
            foreach ($entity['items'] as $item) {
                $carry += $item['count'];
            }

            return $carry;
        }, 0);
    }

    #[\Override]
    protected function transCount(array $parameters): string
    {
        return $this->trans('duplicate.count', $parameters);
    }
}
