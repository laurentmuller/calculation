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
use App\Traits\MathTrait;

/**
 * Report for calculations with empty items.
 *
 * @psalm-import-type CalculationItemType from \App\Repository\CalculationRepository
 */
class CalculationEmptyReport extends AbstractCalculationItemsReport
{
    use EmptyItemsTrait;
    use MathTrait;

    /**
     * The price label.
     */
    private readonly string $priceLabel;

    /**
     * The quantity label.
     */
    private readonly string $quantityLabel;

    /**
     * Constructor.
     *
     * @psalm-param CalculationItemType[] $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller, $entities, 'empty.title', 'empty.description');
        $this->priceLabel = $this->trans('calculationitem.fields.price');
        $this->quantityLabel = $this->trans('calculationitem.fields.quantity');
    }

    /**
     * @param CalculationItemType[] $entities
     */
    protected function computeItemsCount(array $entities): int
    {
        return \array_reduce($entities, fn (int $carry, array $item): int => $carry + \count((array) $item['items']), 0);
    }

    protected function getPriceLabel(): string
    {
        return $this->priceLabel;
    }

    protected function getQuantityLabel(): string
    {
        return $this->quantityLabel;
    }

    protected function transCount(array $parameters): string
    {
        return $this->trans('empty.count', $parameters);
    }
}
