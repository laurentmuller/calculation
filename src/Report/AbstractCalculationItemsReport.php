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

namespace App\Report;

use App\Controller\AbstractController;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;
use App\Util\FormatUtils;

/**
 * Report for calculations with invalid items.
 *
 * @author Laurent Muller
 */
abstract class AbstractCalculationItemsReport extends AbstractArrayReport
{
    /**
     * Constructor.
     *
     * @param AbstractController $controller  the parent controller
     * @param array              $items       the items to render
     * @param string             $title       the title to translate
     * @param string             $description the description to translate
     */
    protected function __construct(AbstractController $controller, array $items, string $title, string $description)
    {
        parent::__construct($controller, $items, self::ORIENTATION_LANDSCAPE);
        $this->header->setDescription($this->trans($description));
        $this->setTitleTrans($title, [], true);
    }

    /**
     * Compute the number of items.
     *
     * @param array $items the calculations
     *
     * @return int the number of items
     */
    abstract protected function computeItemsCount(array $items): int;

    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // new page
        $this->AddPage();

        // table
        $table = $this->createTable();

        // items style
        $style = PdfStyle::getCellStyle()
            ->setTextColor(PdfTextColor::red());

        // add
        foreach ($entities as $entity) {
            $table->startRow()
                ->add(FormatUtils::formatId($entity['id']))
                ->add(FormatUtils::formatDate($entity['date']))
                ->add($entity['stateCode'])
                ->add($entity['customer'])
                ->add($entity['description'])
                ->add($this->formatItems($entity['items']), 1, $style)
                ->endRow();
        }
        PdfStyle::getDefaultStyle()->apply($this);

        // counters
        $parameters = [
            '%calculations%' => \count($entities),
            '%items%' => $this->computeItemsCount($entities),
        ];
        $text = $this->transCount($parameters);

        $margins = $this->setCellMargin(0);
        $this->Cell(0, self::LINE_HEIGHT, $text, self::BORDER_NONE, self::MOVE_TO_NEW_LINE);
        $this->setCellMargin($margins);

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
    private function createTable(): PdfTableBuilder
    {
        // create table
        $columns = [
            PdfColumn::center($this->trans('calculation.fields.id'), 17, true),
            PdfColumn::center($this->trans('calculation.fields.date'), 20, true),
            PdfColumn::left($this->trans('calculation.fields.state'), 12, false),
            PdfColumn::left($this->trans('calculation.fields.customer'), 35, false),
            PdfColumn::left($this->trans('calculation.fields.description'), 60, false),
            PdfColumn::left($this->trans('calculation.fields.items'), 60, false),
        ];

        $table = new PdfTableBuilder($this);

        return $table->addColumns($columns)->outputHeaders();
    }
}
