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
use App\Pdf\Enums\PdfDocumentSize;
use App\Pdf\Enums\PdfDocumentUnit;
use App\Pdf\PdfBarChartTrait;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;
use App\Repository\CalculationRepository;
use App\Traits\MathTrait;
use App\Utils\FormatUtils;

/**
 * Report for calculations by months.
 *
 * @psalm-import-type CalculationByMonthType from CalculationRepository
 *
 * @extends AbstractArrayReport<CalculationByMonthType>
 */
class CalculationByMonthReport extends AbstractArrayReport
{
    use MathTrait;
    use PdfBarChartTrait;

    private const COLOR_ITEM = '#006400';
    private const COLOR_MARGIN = '#8B0000';
    private const PATTERN_CHART = 'MMM Y';
    private const PATTERN_TABLE = 'MMMM Y';

    private float $minMargin;

    /**
     * @psalm-param CalculationByMonthType[] $entities
     */
    public function __construct(
        AbstractController $controller,
        array $entities,
        PdfDocumentOrientation $orientation = PdfDocumentOrientation::PORTRAIT,
        PdfDocumentUnit $unit = PdfDocumentUnit::MILLIMETER,
        PdfDocumentSize $size = PdfDocumentSize::A4
    ) {
        if (\count($entities) > 12) {
            $orientation = PdfDocumentOrientation::LANDSCAPE;
        }
        parent::__construct($controller, $entities, $orientation, $unit, $size);
        $this->SetTitle($this->transChart('title_by_month'));
        $this->minMargin = $controller->getMinMargin();
    }

    protected function doRender(array $entities): bool
    {
        $this->AddPage();
        $this->renderChart($entities);
        $this->renderTable($entities);

        return true;
    }

    private function createTable(): PdfTableBuilder
    {
        return PdfTableBuilder::instance($this)
            ->addColumns(
                PdfColumn::left($this->transChart('fields.month'), 20),
                PdfColumn::right($this->transChart('fields.count'), 25, true),
                PdfColumn::right($this->transChart('fields.net'), 25, true),
                PdfColumn::right($this->transChart('fields.margin_amount'), 25, true),
                PdfColumn::right($this->transChart('fields.margin_percent'), 20, true),
                PdfColumn::right($this->transChart('fields.total'), 25, true),
            )->outputHeaders();
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

    /**
     * @psalm-param CalculationByMonthType[] $entities
     */
    private function renderChart(array $entities): void
    {
        $top = $this->getTopMargin() + $this->getHeader()->getHeight() + self::LINE_HEIGHT;
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
            'formatter' => fn (int|float $value): string => FormatUtils::formatInt($value),
        ];

        $this->barChart(
            rows: $rows,
            axis: $axis,
            y: $top,
            h: 120
        );
        $this->ln();
    }

    /**
     * @psalm-param CalculationByMonthType[] $entities
     */
    private function renderTable(array $entities): void
    {
        $table = $this->createTable();

        foreach ($entities as $entity) {
            $table->addRow(
                $this->formatDate($entity['date'], true),
                FormatUtils::formatInt($entity['count']),
                FormatUtils::formatInt($entity['items']),
                FormatUtils::formatInt($entity['total'] - $entity['items']),
                $this->formatPercent($entity['margin']),
                FormatUtils::formatInt($entity['total'])
            );
        }

        // total
        $count = $this->sum($entities, 'count');
        $items = $this->sum($entities, 'items');
        $total = $this->sum($entities, 'total');
        $net = $total - $items;
        $margin = 1.0 + $this->safeDivide($net, $items);

        $table->addHeaderRow(
            $this->transChart('fields.total'),
            FormatUtils::formatInt($count),
            FormatUtils::formatInt($items),
            FormatUtils::formatInt($net),
            $this->formatPercent($margin, true),
            FormatUtils::formatInt($total)
        );
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
