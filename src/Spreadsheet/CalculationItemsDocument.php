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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Abstract Excel document for the list of calculations with invalid items.
 *
 * @author Laurent Muller
 */
abstract class CalculationItemsDocument extends AbstractArrayDocument
{
    /**
     * Constructor.
     */
    public function __construct(AbstractController $controller, array $entities, string $title)
    {
        parent::__construct($controller, $entities);
        $this->title = $title;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start($this->title, true);

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

    /**
     * Formats the calculation items.
     *
     * @param array $items the calculation items
     *
     * @return string the formatted items
     */
    abstract protected function formatItems(array $items): string;
}
