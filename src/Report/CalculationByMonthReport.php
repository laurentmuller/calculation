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
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\Enums\PdfFontName;
use App\Pdf\Enums\PdfRectangleStyle;
use App\Pdf\Events\PdfCellBackgroundEvent;
use App\Pdf\Events\PdfCellTextEvent;
use App\Pdf\Events\PdfHeadersEvent;
use App\Pdf\Html\HtmlBootstrapColors;
use App\Pdf\Interfaces\PdfDrawCellBackgroundInterface;
use App\Pdf\Interfaces\PdfDrawCellTextInterface;
use App\Pdf\Interfaces\PdfOutputHeadersInterface;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfFillColor;
use App\Pdf\PdfFont;
use App\Pdf\PdfRectangle;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\PdfTextColor;
use App\Pdf\Traits\PdfBarChartTrait;
use App\Utils\FormatUtils;

/**
 * Report for calculations by months.
 *
 * @psalm-import-type CalculationByMonthType from \App\Repository\CalculationRepository
 *
 * @extends AbstractArrayReport<CalculationByMonthType>
 */
class CalculationByMonthReport extends AbstractArrayReport implements PdfDrawCellBackgroundInterface, PdfDrawCellTextInterface, PdfOutputHeadersInterface
{
    use PdfBarChartTrait;

    private const ARROW_DOWN = 116;
    private const ARROW_RIGHT = 116; // same as down but with 90 degrees rotation
    private const ARROW_UP = 115;
    private const COLOR_ITEM = '#006400';
    private const COLOR_MARGIN = '#8B0000';
    private const PATTERN_CHART = 'MMM Y';
    private const PATTERN_TABLE = 'MMMM Y';
    private const RECT_MARGIN = 1.25;

    /*** @psalm-var CalculationByMonthType|null */
    private ?array $currentItem = null;

    private bool $drawHeaders = false;

    /*** @psalm-var CalculationByMonthType|null */
    private ?array $lastItem = null;

    private float $minMargin;

    /**
     * @param CalculationByMonthType[] $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        $orientation = PdfDocumentOrientation::PORTRAIT;
        if (\count($entities) > 12) {
            $orientation = PdfDocumentOrientation::LANDSCAPE;
        }
        parent::__construct($controller, $entities, $orientation);
        $this->SetTitle($this->transChart('title_by_month'));
        $this->minMargin = $controller->getMinMargin();
    }

    public function drawCellBackground(PdfCellBackgroundEvent $event): bool
    {
        if (!$this->drawHeaders) {
            return false;
        }

        return match ($event->index) {
            2 => $this->drawHeaderCell($event->table, $event->bounds, self::COLOR_ITEM),
            3 => $this->drawHeaderCell($event->table, $event->bounds, self::COLOR_MARGIN),
            default => false,
        };
    }

    public function drawCellText(PdfCellTextEvent $event): bool
    {
        if ($this->drawHeaders) {
            return false;
        }

        return match ($event->index) {
            1 => $this->outputArrow($event->bounds, 'count'),
            2 => $this->outputArrow($event->bounds, 'items'),
            3 => $this->outputArrow($event->bounds, 'margin_amount'),
            4 => $this->outputArrow($event->bounds, 'margin_percent', true),
            5 => $this->outputArrow($event->bounds, 'total'),
            default => false
        };
    }

    public function outputHeaders(PdfHeadersEvent $event): void
    {
        $this->drawHeaders = $event->start;
    }

    protected function doRender(array $entities): bool
    {
        $this->AddPage();
        $this->renderChart($entities);
        $this->renderTable($entities);

        return true;
    }

    private function createTable(): PdfTable
    {
        return PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left($this->transChart('fields.month'), 20),
                PdfColumn::right($this->transChart('fields.count'), 25, true),
                PdfColumn::right($this->transChart('fields.net'), 25, true),
                PdfColumn::right($this->transChart('fields.margin_amount'), 25, true),
                PdfColumn::right($this->transChart('fields.margin_percent'), 20, true),
                PdfColumn::right($this->transChart('fields.total'), 25, true),
            )
            ->setBackgroundListener($this)
            ->setHeadersListener($this);
    }

    private function drawHeaderCell(PdfTable $table, PdfRectangle $bounds, string $rgb): bool
    {
        // get color
        $color = PdfFillColor::create($rgb);
        if (!$color instanceof PdfFillColor) {
            return false;
        }

        // default
        $parent = $table->getParent();
        $parent->rectangle($bounds, PdfRectangleStyle::FILL);

        // fill
        $color->apply($parent);
        $parent->Rect(
            $bounds->x() + self::RECT_MARGIN,
            $bounds->y() + self::RECT_MARGIN,
            4.5,
            $bounds->height() - 2.0 * self::RECT_MARGIN,
            PdfRectangleStyle::BOTH
        );

        return true;
    }

    private function formatDate(\DateTimeInterface $date, bool $forTable): string
    {
        $pattern = $forTable ? self::PATTERN_TABLE : self::PATTERN_CHART;

        return \ucfirst(FormatUtils::formatDate(date: $date, pattern: $pattern));
    }

    private function formatPercent(float $value, bool $bold = false): PdfCell
    {
        $cell = new PdfCell(FormatUtils::formatPercent($value, false));
        $style = $bold ? PdfStyle::getHeaderStyle() : PdfStyle::getCellStyle();
        if ($this->isMinMargin($value)) {
            $style->setTextColor(PdfTextColor::red());
        }

        return $cell->setStyle($style);
    }

    private function isMinMargin(float $value): bool
    {
        return !$this->isFloatZero($value) && $value < $this->minMargin;
    }

    private function outputArrow(PdfRectangle $bounds, string $key, bool $percent = false): bool
    {
        if (null === $this->lastItem || null === $this->currentItem) {
            return false;
        }

        $rotate = true;
        $chr = \chr(self::ARROW_RIGHT);
        $color = HtmlBootstrapColors::SECONDARY;
        $precision = $percent ? 2 : 0;
        $oldValue = $this->roundValue((float) $this->lastItem[$key], $precision);
        $newValue = $this->roundValue((float) $this->currentItem[$key], $precision);
        if ($oldValue < $newValue) {
            $rotate = false;
            $chr = \chr(self::ARROW_UP);
            $color = HtmlBootstrapColors::SUCCESS;
        } elseif ($oldValue > $newValue) {
            $rotate = false;
            $chr = \chr(self::ARROW_DOWN);
            $color = HtmlBootstrapColors::DANGER;
        }

        $color->applyTextColor($this);
        $this->SetFont(PdfFontName::ZAPFDINGBATS);
        if ($rotate) {
            $delta = $this->getCellMargin() + $this->GetStringWidth($chr);
            $this->RotateText($chr, 90.0, $bounds->x() + $delta, $bounds->y() + $delta);
        } else {
            $this->Cell(txt: $chr);
        }
        $this->SetXY($bounds->x(), $bounds->y());
        PdfTextColor::default()->apply($this);
        PdfFont::default()->apply($this);

        return false;
    }

    /**
     * @psalm-param CalculationByMonthType[] $entities
     */
    private function renderChart(array $entities): void
    {
        $h = 120;
        $top = $this->tMargin + $this->getHeader()->getHeight() + self::LINE_HEIGHT;
        if (\count($entities) > 12) {
            $h = $this->PageBreakTrigger - $top - self::LINE_HEIGHT;
        }
        $rows = \array_map(function (array $entity): array {
            return [
                'label' => $this->formatDate($entity['date'], false),
                'values' => [
                    ['color' => self::COLOR_ITEM, 'value' => $entity['items']],
                    ['color' => self::COLOR_MARGIN, 'value' => $entity['total'] - $entity['items']],
                ],
            ];
        }, $entities);

        $axis = [
            'min' => 0,
            'formatter' => fn (float $value): string => FormatUtils::formatInt($value),
        ];

        $this->barChart(
            rows: $rows,
            axis: $axis,
            y: $top,
            h: $h
        );
        $this->ln();
    }

    /**
     * @psalm-param CalculationByMonthType[] $entities
     */
    private function renderTable(array $entities): void
    {
        $table = $this->createTable();

        // headers
        $table->outputHeaders();

        // entities
        $table->setTextListener($this);
        foreach ($entities as $entity) {
            $this->currentItem = $entity;
            $table->addRow(
                $this->formatDate($entity['date'], true),
                FormatUtils::formatInt($entity['count']),
                FormatUtils::formatInt($entity['items']),
                FormatUtils::formatInt($entity['margin_amount']),
                $this->formatPercent($entity['margin_percent']),
                FormatUtils::formatInt($entity['total'])
            );
            $this->lastItem = $entity;
        }
        $this->currentItem = $this->lastItem = null;
        $table->setTextListener(null);

        // total
        $count = $this->sum($entities, 'count');
        $items = $this->sum($entities, 'items');
        $total = $this->sum($entities, 'total');
        $net = $total - $items;
        $margin = 1.0 + $this->safeDivide($net, $items);
        $table->setHeadersListener(null)
            ->addHeaderRow(
                $this->transChart('fields.total'),
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

    private function sum(array $entities, string $key): float
    {
        return \array_sum(\array_column($entities, $key));
    }

    private function transChart(string $key): string
    {
        return $this->trans($key, [], 'chart');
    }
}
