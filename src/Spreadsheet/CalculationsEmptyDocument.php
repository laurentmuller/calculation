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
use App\Traits\MathTrait;

/**
 * Spreadsheet document for the list of calculations with empty items.
 */
class CalculationsEmptyDocument extends AbstractCalculationItemsDocument
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
     * @psalm-param array<int, array{
     *      id: int,
     *      date: \DateTimeInterface,
     *      stateCode: string,
     *      customer: string,
     *      description: string,
     *      items: array<array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}>
     *      }> $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller, $entities, 'empty.title');
        $this->priceLabel = $this->trans('calculationitem.fields.price');
        $this->quantityLabel = $this->trans('calculationitem.fields.quantity');
    }

    protected function getPriceLabel(): string
    {
        return $this->priceLabel;
    }

    protected function getQuantityLabel(): string
    {
        return $this->quantityLabel;
    }
}
