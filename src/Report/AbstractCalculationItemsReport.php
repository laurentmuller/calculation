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
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfOrientation;
use fpdf\PdfDocument;

/**
 * Report for calculations with invalid items.
 *
 * @phpstan-import-type CalculationItemEntry from \App\Repository\CalculationRepository
 * @phpstan-import-type CalculationItemType from \App\Repository\CalculationRepository
 *
 * @extends AbstractArrayReport<CalculationItemType>
 */
abstract class AbstractCalculationItemsReport extends AbstractArrayReport
{
    /**
     * @phpstan-param CalculationItemType[] $entities
     */
    protected function __construct(AbstractController $controller, array $entities, string $title, string $description)
    {
        parent::__construct($controller, $entities, PdfOrientation::LANDSCAPE);
        $this->setTitleTrans($title, [], true);
        $this->setDescriptionTrans($description);
    }

    /**
     * Compute the number of items.
     *
     * @param CalculationItemType[] $entities the calculations
     *
     * @return int the number of items
     */
    abstract protected function computeItemsCount(array $entities): int;

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->addPage();
        $table = $this->createTable();
        $style = PdfStyle::getCellStyle()
            ->setTextColor(PdfTextColor::red());
        foreach ($entities as $entity) {
            $table->startRow()
                ->add(FormatUtils::formatId($entity['id']))
                ->add(FormatUtils::formatDate($entity['date']))
                ->add($entity['stateCode'])
                ->add($entity['customer'])
                ->add($entity['description'])
                ->add($this->formatItems($entity['items']), style: $style)
                ->endRow();
        }
        PdfStyle::default()->apply($this);
        $parameters = [
            '%calculations%' => \count($entities),
            '%items%' => $this->computeItemsCount($entities),
        ];
        $text = $this->transCount($parameters);
        $this->useCellMargin(fn (): PdfDocument => $this->cell(text: $text, move: PdfMove::NEW_LINE));

        return true;
    }

    /**
     * Formats the calculation items.
     *
     * @param array $items the calculation items
     *
     * @return string the formatted items
     *
     * @phpstan-param CalculationItemEntry[] $items
     */
    abstract protected function formatItems(array $items): string;

    /**
     * Translate the counters.
     *
     * @param array $parameters the parameters
     *
     * @return string the translated counters
     */
    abstract protected function transCount(array $parameters): string;

    /**
     * Creates the table.
     */
    private function createTable(): PdfTable
    {
        return PdfTable::instance($this)
            ->addColumns(
                $this->centerColumn('calculation.fields.id', 17, true),
                $this->centerColumn('calculation.fields.date', 20, true),
                $this->leftColumn('calculation.fields.state', 20, true),
                $this->leftColumn('calculation.fields.customer', 60),
                $this->leftColumn('calculation.fields.description', 60),
                $this->leftColumn('calculation.fields.items', 70),
            )->outputHeaders();
    }
}
