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

use App\Chart\MonthChart;
use App\Controller\AbstractController;
use App\Model\CalculationsMonth;
use App\Model\CalculationsMonthItem;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Events\PdfCellBackgroundEvent;
use App\Pdf\Events\PdfCellTextEvent;
use App\Pdf\Events\PdfDrawHeadersEvent;
use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\Html\HtmlColorName;
use App\Pdf\Interfaces\PdfChartInterface;
use App\Pdf\Interfaces\PdfColorInterface;
use App\Pdf\Interfaces\PdfDrawCellBackgroundInterface;
use App\Pdf\Interfaces\PdfDrawCellTextInterface;
use App\Pdf\Interfaces\PdfDrawHeadersInterface;
use App\Pdf\PdfCell;
use App\Pdf\PdfStyle;
use App\Pdf\Traits\PdfBarChartTrait;
use App\Pdf\Traits\PdfChartLegendTrait;
use App\Report\Table\ReportTable;
use App\Traits\MathTrait;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfFontName;
use fpdf\Enums\PdfOrientation;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfRectangle;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Report for calculations by months.
 *
 * @extends AbstractArrayReport<CalculationsMonthItem>
 */
class CalculationByMonthReport extends AbstractArrayReport implements PdfChartInterface, PdfDrawCellBackgroundInterface, PdfDrawCellTextInterface, PdfDrawHeadersInterface
{
    use MathTrait;
    use PdfBarChartTrait;
    use PdfChartLegendTrait;

    private const int ARROW_DOWN = 116;
    private const int ARROW_RIGHT = 116; // same as down but with 90 degrees rotation
    private const int ARROW_UP = 115;
    private const string PATTERN_CHART = 'MMM Y';
    private const string PATTERN_TABLE = 'MMMM Y';
    private const float RECT_MARGIN = 1.25;
    private const float RECT_WIDTH = 4.5;

    /** @phpstan-var \WeakMap<PdfColorInterface, PdfTextColor> */
    private \WeakMap $colors;
    private ?CalculationsMonthItem $currentItem = null;
    private bool $drawHeaders = false;
    private ?CalculationsMonthItem $lastItem = null;
    private float $minMargin;

    public function __construct(
        AbstractController $controller,
        private readonly CalculationsMonth $month,
        private readonly UrlGeneratorInterface $generator
    ) {
        $orientation = $month->count() > 12 ? PdfOrientation::LANDSCAPE : PdfOrientation::PORTRAIT;
        parent::__construct($controller, $month->items, $orientation);
        $this->setTranslatedTitle('chart.month.title');
        $this->minMargin = $controller->getMinMargin();
        $this->colors = new \WeakMap();
    }

    #[\Override]
    public function drawCellBackground(PdfCellBackgroundEvent $event): bool
    {
        if (!$this->lastItem instanceof CalculationsMonthItem || !$this->currentItem instanceof CalculationsMonthItem) {
            return false;
        }

        return match ($event->index) {
            1 => $this->outputArrow(
                $event->bounds,
                $this->lastItem->count,
                $this->currentItem->count
            ),
            2 => $this->outputArrow(
                $event->bounds,
                $this->lastItem->items,
                $this->currentItem->items
            ),
            3 => $this->outputArrow(
                $event->bounds,
                $this->lastItem->marginAmount,
                $this->currentItem->marginAmount
            ),
            4 => $this->outputArrow(
                $event->bounds,
                $this->lastItem->marginPercent,
                $this->currentItem->marginPercent,
                true
            ),
            5 => $this->outputArrow(
                $event->bounds,
                $this->lastItem->total,
                $this->currentItem->total
            ),
            default => false
        };
    }

    #[\Override]
    public function drawCellText(PdfCellTextEvent $event): bool
    {
        if (!$this->drawHeaders) {
            return false;
        }

        return match ($event->index) {
            2 => $this->drawHeaderCell($event, MonthChart::COLOR_AMOUNT),
            3 => $this->drawHeaderCell($event, MonthChart::COLOR_MARGIN),
            default => false,
        };
    }

    #[\Override]
    public function drawHeaders(PdfDrawHeadersEvent $event): true
    {
        $cells = [];
        $table = $event->table;
        $columns = $event->getColumns();
        foreach ($columns as $index => $column) {
            if (4 === $index) {
                continue;
            }
            $cols = 3 === $index ? 2 : 1;
            $alignment = 3 === $index ? PdfTextAlignment::CENTER : $column->getAlignment();
            $cells[] = new PdfCell(
                $column->getText(),
                $cols,
                $event->headerStyle,
                $alignment
            );
        }
        $this->drawHeaders = true;
        $table->addRow(...$cells);
        $this->drawHeaders = false;

        return true;
    }

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->addPage();
        $this->renderChart($entities);
        $this->renderTable($entities);

        return true;
    }

    private function createTable(): ReportTable
    {
        return ReportTable::fromReport($this)
            ->addColumns(
                $this->leftColumn('chart.month.fields.month', 20),
                $this->rightColumn('calculation.list.title', 22, true),
                $this->rightColumn('calculationgroup.fields.amount', 25, true),
                $this->rightColumn('calculation.fields.margin', 20, true),
                $this->rightColumn('', 18, true),
                $this->rightColumn('calculation.fields.total', 25, true),
            )
            ->setHeadersListener($this)
            ->setTextListener($this)
            ->outputHeaders();
    }

    private function drawHeaderCell(PdfCellTextEvent $event, HtmlColorName $colorName): bool
    {
        $text = $event->text;
        $table = $event->table;
        $bounds = $event->bounds;
        $parent = $table->getParent();
        $textWidth = $parent->getStringWidth($text) + $parent->getCellMargin();
        $offset = ($bounds->width - $textWidth - self::RECT_WIDTH) / 2.0;

        /** @phpstan-var PdfFillColor $color */
        $color = PdfFillColor::create($colorName->value);
        $color->apply($parent);

        $parent->rect(
            $bounds->x + $offset,
            $bounds->y + self::RECT_MARGIN,
            self::RECT_WIDTH,
            $bounds->height - 2.0 * self::RECT_MARGIN,
            PdfRectangleStyle::BOTH
        );

        $parent->setX($bounds->x + $offset + self::RECT_WIDTH);
        $parent->cell(width: $textWidth, text: $text);

        return true;
    }

    private function getArrowColor(PdfColorInterface $color): PdfTextColor
    {
        if (!isset($this->colors[$color])) {
            return $this->colors[$color] = $color->getTextColor();
        }

        return $this->colors[$color];
    }

    private function getDateCell(DatePoint $date, bool $forTable): string
    {
        $pattern = $forTable ? self::PATTERN_TABLE : self::PATTERN_CHART;

        return FormatUtils::formatDate(date: $date, pattern: $pattern);
    }

    private function getPercentStyle(float $value, bool $bold = false): PdfStyle
    {
        $style = $bold ? PdfStyle::getHeaderStyle() : PdfStyle::getCellStyle();
        if ($this->isBelow($this->minMargin, $value)) {
            $style->setTextColor(PdfTextColor::red());
        }

        return $style;
    }

    private function getURL(CalculationsMonthItem $item): string
    {
        $parameters = ['search' => $item->getSearchDate()];

        return $this->generator->generate('calculation_index', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function outputArrow(
        PdfRectangle $bounds,
        float $lastValue,
        float $currentValue,
        bool $percent = false
    ): false {
        $rotate = false;
        $precision = $percent ? 2 : 0;
        $oldValue = $this->roundValue($lastValue, $precision);
        $newValue = $this->roundValue($currentValue, $precision);
        if ($oldValue < $newValue) {
            $chr = \chr(self::ARROW_UP);
            $color = HtmlBootstrapColor::SUCCESS;
        } elseif ($oldValue > $newValue) {
            $chr = \chr(self::ARROW_DOWN);
            $color = HtmlBootstrapColor::DANGER;
        } else {
            $rotate = true;
            $chr = \chr(self::ARROW_RIGHT);
            $color = HtmlBootstrapColor::SECONDARY;
        }

        $this->getArrowColor($color)->apply($this);
        $oldFont = $this->getCurrentFont();
        $this->setFont(PdfFontName::ZAPFDINGBATS);
        $width = $this->getStringWidth($chr);
        if ($rotate) {
            $delta = $this->getCellMargin() + $width;
            $this->rotateText($chr, 90.0, $bounds->x + $delta, $bounds->y + $delta);
        } else {
            $this->cell(width: $width, text: $chr);
        }
        $oldFont->apply($this);

        return false;
    }

    /**
     * @param CalculationsMonthItem[] $entities
     */
    private function renderChart(array $entities): void
    {
        $h = 100;
        $newPage = \count($entities) > 12;
        $top = $this->getTopMargin() + $this->getHeader()->getHeight();
        if ($newPage) {
            $h = $this->pageBreakTrigger - $top - 2.0 * self::LINE_HEIGHT;
        }
        $rows = \array_map(fn (CalculationsMonthItem $entity): array => [
            'link' => $this->getURL($entity),
            'label' => $this->cleanText($this->getDateCell($entity->date, false)),
            'values' => [
                ['color' => MonthChart::COLOR_AMOUNT->value, 'value' => $entity->items],
                ['color' => MonthChart::COLOR_MARGIN->value, 'value' => $entity->marginAmount],
            ],
        ], $entities);
        $axis = [
            'min' => 0,
            'formatter' => FormatUtils::formatInt(...),
        ];
        $this->renderBarChart(rows: $rows, axis: $axis, y: $top + self::LINE_HEIGHT, height: $h);
        if ($newPage) {
            $this->addPage();
        } else {
            $this->lineBreak();
        }
    }

    /**
     * @param CalculationsMonthItem[] $entities
     */
    private function renderTable(array $entities): void
    {
        $table = $this->createTable();

        // entities
        $table->setBackgroundListener($this);
        $width = $this->getPrintableWidth();
        foreach ($entities as $entity) {
            $x = $this->getX();
            $y = $this->getY();
            $this->currentItem = $entity;
            $margin = $entity->marginPercent;
            $table->startRow()
                ->add($this->getDateCell($entity->date, true))
                ->addCellInt($entity->count)
                ->addCellInt($entity->items)
                ->addCellInt($entity->marginAmount)
                ->addCellPercent($margin, style: $this->getPercentStyle($margin))
                ->addCellInt($entity->total)
                ->endRow();
            $link = $this->getURL($entity);
            $this->link($x, $y, $width, $this->getY() - $y, $link);
            $this->lastItem = $entity;
        }
        $this->currentItem = null;
        $this->lastItem = null;
        $table->setBackgroundListener(null)
            ->setHeadersListener(null)
            ->setTextListener(null);

        // total
        $total = $this->month->total;
        $margin = $total->marginPercent;
        $table->startHeaderRow()
            ->addCellTrans('calculation.fields.total')
            ->addCellInt($total->count)
            ->addCellInt($total->items)
            ->addCellInt($total->marginAmount)
            ->addCellPercent($margin, style: $this->getPercentStyle($margin, true))
            ->addCellInt($total->total)
            ->endRow();
    }

    private function roundValue(float $value, int $precision = 0): float
    {
        if (0 === $precision) {
            return \round($value, $precision);
        }

        $power = 10.0 ** (float) $precision;

        return \floor($value * $power) / $power;
    }
}
