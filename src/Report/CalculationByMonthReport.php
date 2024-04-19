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
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Events\PdfCellBackgroundEvent;
use App\Pdf\Events\PdfCellTextEvent;
use App\Pdf\Events\PdfPdfDrawHeadersEvent;
use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\Interfaces\PdfChartInterface;
use App\Pdf\Interfaces\PdfColorInterface;
use App\Pdf\Interfaces\PdfDrawCellBackgroundInterface;
use App\Pdf\Interfaces\PdfDrawCellTextInterface;
use App\Pdf\Interfaces\PdfDrawHeadersInterface;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfBarChartTrait;
use App\Pdf\Traits\PdfChartLegendTrait;
use App\Traits\ArrayTrait;
use App\Utils\FormatUtils;
use fpdf\PdfFontName;
use fpdf\PdfOrientation;
use fpdf\PdfRectangle;
use fpdf\PdfRectangleStyle;
use fpdf\PdfTextAlignment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Report for calculations by months.
 *
 * @extends AbstractArrayReport<CalculationByMonthType>
 *
 * @psalm-import-type CalculationByMonthType from \App\Repository\CalculationRepository
 * @psalm-import-type ColorStringType from PdfChartInterface
 */
class CalculationByMonthReport extends AbstractArrayReport implements PdfChartInterface, PdfDrawCellBackgroundInterface, PdfDrawCellTextInterface, PdfDrawHeadersInterface
{
    use ArrayTrait;
    use PdfBarChartTrait;
    use PdfChartLegendTrait;

    private const ARROW_DOWN = 116;
    private const ARROW_RIGHT = 116; // same as down but with 90 degrees rotation
    private const ARROW_UP = 115;
    private const COLOR_ITEM = '#006400';
    private const COLOR_MARGIN = '#8B0000';
    private const PATTERN_CHART = 'MMM Y';
    private const PATTERN_TABLE = 'MMMM Y';
    private const RECT_MARGIN = 1.25;
    private const RECT_WIDTH = 4.5;
    /** @psalm-var \WeakMap<PdfColorInterface, PdfTextColor>  */
    private \WeakMap $colors;
    /*** @psalm-var CalculationByMonthType|null */
    private ?array $currentItem = null;
    private bool $drawHeaders = false;
    /*** @psalm-var CalculationByMonthType|null */
    private ?array $lastItem = null;
    private float $minMargin;

    /**
     * @param CalculationByMonthType[] $entities
     *
     * @psalm-suppress PropertyTypeCoercion
     */
    public function __construct(
        AbstractController $controller,
        array $entities,
        private readonly UrlGeneratorInterface $generator
    ) {
        $orientation = PdfOrientation::PORTRAIT;
        if (\count($entities) > 12) {
            $orientation = PdfOrientation::LANDSCAPE;
        }
        parent::__construct($controller, $entities, $orientation);
        $this->setTitle($this->trans('chart.month.title'));
        $this->minMargin = $controller->getMinMargin();
        $this->colors = new \WeakMap();
    }

    public function drawCellBackground(PdfCellBackgroundEvent $event): bool
    {
        return match ($event->index) {
            1 => $this->outputArrow($event->bounds, 'count'),
            2 => $this->outputArrow($event->bounds, 'items'),
            3 => $this->outputArrow($event->bounds, 'margin_amount'),
            4 => $this->outputArrow($event->bounds, 'margin_percent', true),
            5 => $this->outputArrow($event->bounds, 'total'),
            default => false
        };
    }

    public function drawCellText(PdfCellTextEvent $event): bool
    {
        if (!$this->drawHeaders) {
            return false;
        }

        return match ($event->index) {
            2 => $this->drawHeaderCell($event, self::COLOR_ITEM),
            3 => $this->drawHeaderCell($event, self::COLOR_MARGIN),
            default => false,
        };
    }

    public function drawHeaders(PdfPdfDrawHeadersEvent $event): true
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

    protected function doRender(array $entities): bool
    {
        $this->addPage();
        $this->renderChart($entities);
        $this->renderTable($entities);

        return true;
    }

    private function createTable(): PdfTable
    {
        return PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('chart.month.fields.month'), 20),
                PdfColumn::right($this->trans('calculation.list.title'), 22, true),
                PdfColumn::right($this->trans('calculationgroup.fields.amount'), 25, true),
                PdfColumn::right($this->trans('calculation.fields.margin'), 20, true),
                PdfColumn::right('', 18, true),
                PdfColumn::right($this->trans('calculation.fields.total'), 25, true),
            )
            ->setHeadersListener($this)
            ->setTextListener($this)
            ->outputHeaders();
    }

    private function drawHeaderCell(PdfCellTextEvent $event, string $rgb): bool
    {
        $color = PdfFillColor::create($rgb);
        if (!$color instanceof PdfFillColor) {
            return false;
        }

        $text = $event->text;
        $table = $event->table;
        $bounds = $event->bounds;
        $parent = $table->getParent();
        $textWidth = $parent->getStringWidth($text) + $parent->getCellMargin();
        $offset = ($bounds->width - $textWidth - self::RECT_WIDTH) / 2.0;

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

    private function formatDate(\DateTimeInterface $date, bool $forTable): string
    {
        $pattern = $forTable ? self::PATTERN_TABLE : self::PATTERN_CHART;

        return FormatUtils::formatDate(date: $date, pattern: $pattern);
    }

    private function formatPercent(float $value, bool $bold = false): PdfCell
    {
        $cell = new PdfCell(FormatUtils::formatPercent($value));
        $style = $bold ? PdfStyle::getHeaderStyle() : PdfStyle::getCellStyle();
        if ($this->isMinMargin($value)) {
            $style->setTextColor(PdfTextColor::red());
        }

        return $cell->setStyle($style);
    }

    private function getArrowColor(PdfColorInterface $color): PdfTextColor
    {
        if (!isset($this->colors[$color])) {
            return $this->colors[$color] = $color->getTextColor();
        }

        return $this->colors[$color];
    }

    private function getURL(\DateTimeInterface $date): string
    {
        $parameters = ['search' => $date->format('m.Y')];

        return $this->generator->generate('calculation_table', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function isMinMargin(float $value): bool
    {
        return !$this->isFloatZero($value) && $value < $this->minMargin;
    }

    private function outputArrow(PdfRectangle $bounds, string $key, bool $percent = false): false
    {
        if (null === $this->lastItem || null === $this->currentItem) {
            return false;
        }

        $rotate = false;
        $precision = $percent ? 2 : 0;
        $oldValue = $this->roundValue((float) $this->lastItem[$key], $precision);
        $newValue = $this->roundValue((float) $this->currentItem[$key], $precision);
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
     * @psalm-param CalculationByMonthType[] $entities
     */
    private function renderChart(array $entities): void
    {
        $h = 100;
        $newPage = \count($entities) > 12;
        $top = $this->topMargin + $this->getHeader()->getHeight();
        if ($newPage) {
            $h = $this->pageBreakTrigger - $top - 2.0 * self::LINE_HEIGHT;
        }
        $rows = \array_map(fn (array $entity): array => [
            'label' => $this->formatDate($entity['date'], false),
            'link' => $this->getURL($entity['date']),
            'values' => [
                ['color' => self::COLOR_ITEM, 'value' => $entity['items']],
                ['color' => self::COLOR_MARGIN, 'value' => $entity['margin_amount']],
            ],
        ], $entities);
        $axis = [
            'min' => 0,
            'formatter' => fn (float $value): string => FormatUtils::formatInt($value),
        ];
        $this->renderBarChart(rows: $rows, axis: $axis, y: $top + self::LINE_HEIGHT, h: $h);
        if ($newPage) {
            $this->addPage();
        } else {
            $this->lineBreak();
        }
    }

    /**
     * @psalm-param CalculationByMonthType[] $entities
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
            $table->addRow(
                $this->formatDate($entity['date'], true),
                FormatUtils::formatInt($entity['count']),
                FormatUtils::formatInt($entity['items']),
                FormatUtils::formatInt($entity['margin_amount']),
                $this->formatPercent($entity['margin_percent']),
                FormatUtils::formatInt($entity['total'])
            );
            $link = $this->getURL($entity['date']);
            $this->link($x, $y, $width, $this->getY() - $y, $link);
            $this->lastItem = $entity;
        }
        $this->currentItem = $this->lastItem = null;
        $table->setBackgroundListener(null)
            ->setHeadersListener(null)
            ->setTextListener(null);

        // total
        $count = $this->getColumnSum($entities, 'count');
        $items = $this->getColumnSum($entities, 'items');
        $total = $this->getColumnSum($entities, 'total');
        $net = $total - $items;
        $margin = 1.0 + $this->safeDivide($net, $items);
        $table->addHeaderRow(
            $this->trans('calculation.fields.total'),
            FormatUtils::formatInt($count),
            FormatUtils::formatInt($items),
            FormatUtils::formatInt($net),
            $this->formatPercent($margin, true),
            FormatUtils::formatInt($total)
        );
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
