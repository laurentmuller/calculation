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
 * @psalm-import-type CalculationItemType from \App\Repository\CalculationRepository
 * @psalm-import-type CalculationItemEntry from \App\Repository\CalculationRepository
 */
class CalculationsDuplicateReport extends AbstractCalculationItemsReport
{
    use DuplicateItemsTrait;

    /**
     * @psalm-param CalculationItemType[] $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller, $entities, 'duplicate.title', 'duplicate.description');
    }

    /**
     * @psalm-param CalculationItemType[] $entities
     */
    protected function computeItemsCount(array $entities): int
    {
        return \array_reduce($entities, function (int $carry, array $entity): int {
            /** @psalm-var CalculationItemEntry $item */
            foreach ($entity['items'] as $item) {
                $carry += $item['count'];
            }

            return $carry;
        }, 0);
    }

    protected function transCount(array $parameters): string
    {
        return $this->trans('duplicate.count', $parameters);
    }
}
