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
use App\Traits\EmptyItemsTrait;

/**
 * Report for calculations with empty items.
 *
 * @phpstan-import-type CalculationItemType from \App\Repository\CalculationRepository
 */
class CalculationsEmptyReport extends AbstractCalculationItemsReport
{
    use EmptyItemsTrait;

    /**
     * @phpstan-param CalculationItemType[] $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller, $entities, 'empty.title', 'empty.description');
    }

    /**
     * @param CalculationItemType[] $entities
     */
    #[\Override]
    protected function computeItemsCount(array $entities): int
    {
        return \array_reduce(
            $entities,
            static fn (int $carry, array $entity): int => $carry + \count($entity['items']),
            0
        );
    }

    #[\Override]
    protected function getPriceLabel(): string
    {
        return $this->trans('calculationitem.fields.price');
    }

    #[\Override]
    protected function getQuantityLabel(): string
    {
        return $this->trans('calculationitem.fields.quantity');
    }

    #[\Override]
    protected function transCount(array $parameters): string
    {
        return $this->trans('empty.count', $parameters);
    }
}
