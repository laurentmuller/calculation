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

use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;
use App\Repository\CalculationStateRepository;
use App\Traits\MathTrait;
use App\Utils\FormatUtils;

/**
 * Report for calculations by states.
 *
 * @psalm-import-type QueryCalculationType from CalculationStateRepository
 *
 * @extends AbstractArrayReport<QueryCalculationType>
 */
class CalculationByStateReport extends AbstractArrayReport
{
    use MathTrait;

    /**
     * @psalm-param  QueryCalculationType[] $entities
     */
    protected function doRender(array $entities): bool
    {
        $this->SetTitle($this->transChart('title_by_state'));

        $this->AddPage();
        $table = $this->createTable();

        foreach ($entities as $entity) {
            $table->startRow()->addValues(
                $entity['code'],
                FormatUtils::formatInt($entity['count']),
                $this->formatPercent($entity['percentCalculation'], 2),
                FormatUtils::formatInt($entity['items']),
                FormatUtils::formatInt($entity['marginAmount']),
                $this->formatPercent($entity['margin'], 0, true),
                FormatUtils::formatInt($entity['total']),
                $this->formatPercent($entity['percentAmount'], 2)
            )->endRow();
        }

        // total
        $count = $this->sum($entities, 'count');
        $percentCalculation = $this->sum($entities, 'percentCalculation');
        $items = $this->sum($entities, 'items');
        $marginAmount = $this->sum($entities, 'marginAmount');
        $total = $this->sum($entities, 'total');
        $net = $total - $items;
        $margin = 1.0 + $this->safeDivide($net, $items);
        $percentAmount = $this->sum($entities, 'percentAmount');

        $table->startHeaderRow()->addValues(
            $this->transChart('fields.total'),
            FormatUtils::formatInt($count),
            $this->formatPercent($percentCalculation, 2, bold: true),
            FormatUtils::formatInt($items),
            FormatUtils::formatInt($marginAmount),
            $this->formatPercent($margin, 0, bold: true),
            FormatUtils::formatInt($total),
            $this->formatPercent($percentAmount, 2, bold: true),
        )->endRow();

        return true;
    }

    private function createTable(): PdfTableBuilder
    {
        $columns = [
            PdfColumn::left($this->transChart('fields.state'), 20),
            PdfColumn::right($this->transChart('fields.count'), 25, true),
            PdfColumn::right('%', 15, true),
            PdfColumn::right($this->transChart('fields.net'), 20, true),
            PdfColumn::right($this->transChart('fields.margin_amount'), 20, true),
            PdfColumn::right($this->transChart('fields.margin_percent'), 20, true),
            PdfColumn::right($this->transChart('fields.total'), 20, true),
            PdfColumn::right('%', 15, true),
        ];

        return PdfTableBuilder::instance($this)
            ->addColumns(...$columns)
            ->outputHeaders();
    }

    private function formatPercent(float $value, int $decimals = 1, bool $useStyle = false, bool $bold = false): PdfCell
    {
        $style = $bold ? PdfStyle::getHeaderStyle() : PdfStyle::getCellStyle();
        $cell = new PdfCell(FormatUtils::formatPercent($value, false, $decimals, \NumberFormatter::ROUND_HALFEVEN));
        if ($useStyle && $this->isMinMargin($value)) {
            $style->setTextColor(PdfTextColor::red());
        }
        $cell->setStyle($style);

        return $cell;
    }

    private function isMinMargin(float $value): bool
    {
        return !$this->isFloatZero($value) && $value < $this->controller->getMinMargin();
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
