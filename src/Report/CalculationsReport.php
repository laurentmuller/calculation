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
use App\Entity\Calculation;
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;
use App\Traits\GroupByTrait;
use App\Traits\MathTrait;
use App\Utils\FormatUtils;

/**
 * Report for the list of calculations.
 *
 * @extends AbstractArrayReport<Calculation>
 */
class CalculationsReport extends AbstractArrayReport
{
    use GroupByTrait;
    use MathTrait;

    /**
     * The sum of items calculations.
     */
    private float $items = 0.0;

    /**
     * The minimum margin style.
     */
    private ?PdfStyle $marginStyle = null;

    /**
     * The minimum margin.
     */
    private readonly float $minMargin;

    /**
     * The sum of overall calculations.
     */
    private float $overall = 0.0;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     * @param Calculation[]      $entities   the calculations to render
     * @param bool               $grouped    true if calculations are grouped by state
     */
    public function __construct(AbstractController $controller, array $entities, protected bool $grouped = true)
    {
        parent::__construct($controller, $entities, PdfDocumentOrientation::LANDSCAPE);
        $this->minMargin = $controller->getMinMargin();
    }

    /**
     * @param Calculation[] $entities
     */
    protected function doRender(array $entities): bool
    {
        if (empty($this->getTitle())) {
            $this->setTitleTrans('calculation.list.title');
        }

        $this->AddPage();
        $this->items = $this->overall = 0.0;
        $table = $this->grouped ? $this->outputByGroup($entities) : $this->outputByList($entities);
        $margins = $this->isFloatZero($this->items) ? 0 : $this->safeDivide($this->overall, $this->items);

        $style = null;
        if (!$this->isFloatZero($margins) && $margins < $this->minMargin) {
            $style = PdfStyle::getHeaderStyle()->setTextColor(PdfTextColor::red());
        }
        $text = $this->translateCount($entities, 'counters.calculations');
        $columns = $table->getColumnsCount() - 3;
        $table->getColumns()[0]->setAlignment(PdfTextAlignment::LEFT)
            ->setFixed(false);
        $table->startHeaderRow()
            ->add($text, $columns)
            ->add(FormatUtils::formatAmount($this->items))
            ->add(text: FormatUtils::formatPercent($margins), style: $style)
            ->add(FormatUtils::formatAmount($this->overall))
            ->endRow();

        return true;
    }

    /**
     * Creates the table.
     *
     * @param bool $grouped true if calculations are grouped by state
     */
    private function createTable(bool $grouped): PdfGroupTableBuilder
    {
        $columns = [
            PdfColumn::center($this->trans('calculation.fields.id'), 17, true),
            PdfColumn::center($this->trans('calculation.fields.date'), 20, true),
        ];
        if (!$grouped) {
            $columns[] = PdfColumn::left($this->trans('calculation.fields.state'), 20, true);
        }
        $columns = \array_merge($columns, [
            PdfColumn::left($this->trans('calculation.fields.customer'), 35),
            PdfColumn::left($this->trans('calculation.fields.description'), 65),
            PdfColumn::right($this->trans('report.calculation.amount'), 25, true),
            PdfColumn::right($this->trans('report.calculation.margin_percent'), 20, true),
            PdfColumn::right($this->trans('calculation.fields.total'), 25, true),
        ]);

        return PdfGroupTableBuilder::instance($this)
            ->addColumns(...$columns)
            ->outputHeaders();
    }

    /**
     * Gets the style for the margin below.
     *
     * @param Calculation $calculation the calculation to get style for
     *
     * @return PdfStyle|null the margin style, if applicable, null otherwise
     */
    private function getMarginStyle(Calculation $calculation): ?PdfStyle
    {
        if ($calculation->isMarginBelow($this->minMargin)) {
            if (!$this->marginStyle instanceof PdfStyle) {
                $this->marginStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());
            }

            return $this->marginStyle;
        }

        return null;
    }

    /**
     * Outputs the calculations grouped by state.
     *
     * @param Calculation[] $entities the calculations to render
     *
     * @return PdfGroupTableBuilder the table builder
     */
    private function outputByGroup(array $entities): PdfGroupTableBuilder
    {
        /** @var array<string, Calculation[]> $groups */
        $groups = $this->groupBy($entities, fn (Calculation $c): string => (string) $c->getStateCode());

        $table = $this->createTable(true);
        foreach ($groups as $group => $items) {
            $this->addBookmark($group);
            $table->setGroupKey($group);
            foreach ($items as $item) {
                $this->outputItem($table, $item, true);
            }
        }

        return $table;
    }

    /**
     * Output the calculations as list.
     *
     * @param Calculation[] $entities the calculations to render
     *
     * @return PdfGroupTableBuilder the table builder
     */
    private function outputByList(array $entities): PdfGroupTableBuilder
    {
        $table = $this->createTable(false);
        foreach ($entities as $entity) {
            $this->outputItem($table, $entity, false);
        }

        return $table;
    }

    /**
     * Output a single calculation.
     *
     * @param PdfGroupTableBuilder $table        the table to write in
     * @param Calculation          $c            the calculation to output
     * @param bool                 $groupByState true if grouped by state
     */
    private function outputItem(PdfGroupTableBuilder $table, Calculation $c, bool $groupByState): void
    {
        $style = $this->getMarginStyle($c);
        $table->startRow()
            ->add($c->getFormattedId())
            ->add($c->getFormattedDate());
        if (!$groupByState) {
            $table->add($c->getStateCode());
        }
        $table->add($c->getCustomer())
            ->add($c->getDescription())
            ->add(FormatUtils::formatAmount($c->getItemsTotal()))
            ->add(text: FormatUtils::formatPercent($c->getOverallMargin()), style: $style)
            ->add(FormatUtils::formatAmount($c->getOverallTotal()))
            ->endRow();

        $this->items += $c->getItemsTotal();
        $this->overall += $c->getOverallTotal();
    }
}
