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
use App\Traits\MathTrait;

/**
 * Spreadsheet document for the list of calculations with empty items.
 */
class CalculationsEmptyDocument extends AbstractCalculationItemsDocument
{
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
     *      items: array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}
     *      }> $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller, $entities, 'empty.title');
        $this->priceLabel = $this->trans('calculationitem.fields.price');
        $this->quantityLabel = $this->trans('calculationitem.fields.quantity');
    }

    /**
     * {@inheritdoc}
     */
    protected function formatItems(array $items): string
    {
        $result = \array_map(function (array $item): string {
            $founds = [];
            if ($this->isFloatZero((float) $item['price'])) {
                $founds[] = $this->priceLabel;
            }
            if ($this->isFloatZero((float) $item['quantity'])) {
                $founds[] = $this->quantityLabel;
            }

            return \sprintf('%s (%s)', (string) $item['description'], \implode(', ', $founds));
        }, $items);

        return \implode("\n", $result);
    }
}
