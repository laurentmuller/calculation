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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Abstract Spreadsheet document for the list of calculations with invalid items.
 *
 * @phpstan-import-type CalculationItemType from \App\Repository\CalculationRepository
 *
 * @extends AbstractArrayDocument<CalculationItemType>
 */
abstract class AbstractCalculationItemsDocument extends AbstractArrayDocument
{
    /**
     * @phpstan-param CalculationItemType[] $entities
     */
    public function __construct(AbstractController $controller, array $entities, string $title)
    {
        parent::__construct($controller, $entities);
        $this->setTitle($title);
    }

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->start((string) $this->getTitle(), true);

        $sheet = $this->getActiveSheet();
        $sheet->setForeground(6, Color::COLOR_RED)
            ->setWrapText(6);

        $row = $sheet->setHeaders([
            'calculation.fields.id' => HeaderFormat::id(Alignment::VERTICAL_TOP),
            'calculation.fields.date' => HeaderFormat::date(Alignment::VERTICAL_TOP),
            'calculation.fields.state' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
            'calculation.fields.customer' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
            'calculation.fields.description' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
            'calculationgroup.fields.items' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
        ]);

        foreach ($entities as $entity) {
            $sheet->setRowValues($row++, [
                $entity['id'],
                $entity['date'],
                $entity['stateCode'],
                $entity['customer'],
                $entity['description'],
                $this->formatItems($entity['items']),
            ]);
        }
        $sheet->finish();

        return true;
    }

    /**
     * Formats the calculation items.
     *
     * @param array $items the calculation items
     *
     * @phpstan-param array<array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}> $items
     *
     * @return string the formatted items
     */
    abstract protected function formatItems(array $items): string;
}
