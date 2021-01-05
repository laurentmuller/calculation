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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Excel document for the list of calculations with empty items.
 *
 * @author Laurent Muller
 */
class CalculationEmptyDocument extends AbstractArrayDocument
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
        parent::__construct($controller, $entities);
        $this->priceLabel = $this->trans('calculationitem.fields.price');
        $this->quantityLabel = $this->trans('calculationitem.fields.quantity');
    }

    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('empty.title', true);

        // red color and word wrap for items
        $this->setForeground(6, Color::COLOR_RED)
            ->setWrapText(6);

        // headers
        $this->setHeaderValues([
            'calculation.fields.id' => [Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_TOP],
            'calculation.fields.date' => [Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_TOP],
            'calculation.fields.state' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'calculation.fields.customer' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'calculation.fields.description' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'calculationgroup.fields.items' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
        ]);

        // formats
        $this->setFormatId(1)
            ->setFormatDate(2);

        // rows
        $row = 2;
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity['id'],
                $entity['date'],
                $entity['stateCode'],
                $entity['customer'],
                $entity['description'],
                $this->formatItems($entity['items']),
            ]);
        }

        $this->finish();

        return true;
    }

    private function formatItems(array $items): string
    {
        $result = \array_map(function (array $item) {
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
