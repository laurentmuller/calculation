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

namespace App\Spreadsheet;

use App\Controller\AbstractController;
use App\Traits\MathTrait;

/**
 * Spreadsheet document for the list of calculations with empty items.
 *
 * @author Laurent Muller
 */
class CalculationsEmptyDocument extends AbstractCalculationItemsDocument
{
    use MathTrait;

    /**
     * The price label.
     *
     * @var string
     */
    private $priceLabel;

    /**
     * The quantity label.
     *
     * @var string
     */
    private $quantityLabel;

    /**
     * Constructor.
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
            if ($this->isFloatZero($item['price'])) {
                $founds[] = $this->priceLabel;
            }
            if ($this->isFloatZero($item['quantity'])) {
                $founds[] = $this->quantityLabel;
            }

            return \sprintf('%s (%s)', $item['description'], \implode(', ', $founds));
        }, $items);

        return \implode("\n", $result);
    }
}
