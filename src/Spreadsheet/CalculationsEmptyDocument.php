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

namespace App\Spreadsheet;

use App\Controller\AbstractController;
use App\Traits\EmptyItemsTrait;

/**
 * Spreadsheet document for the list of calculations with empty items.
 *
 * @phpstan-import-type CalculationItemType from \App\Repository\CalculationRepository
 */
class CalculationsEmptyDocument extends AbstractCalculationItemsDocument
{
    use EmptyItemsTrait;

    /**
     * The price label.
     */
    private readonly string $priceLabel;

    /**
     * The quantity label.
     */
    private readonly string $quantityLabel;

    /**
     * @phpstan-param CalculationItemType[] $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller, $entities, 'empty.title');
        $this->setDescriptionTrans('empty.description');
        $this->priceLabel = $this->trans('calculationitem.fields.price');
        $this->quantityLabel = $this->trans('calculationitem.fields.quantity');
    }

    #[\Override]
    protected function getPriceLabel(): string
    {
        return $this->priceLabel;
    }

    #[\Override]
    protected function getQuantityLabel(): string
    {
        return $this->quantityLabel;
    }
}
