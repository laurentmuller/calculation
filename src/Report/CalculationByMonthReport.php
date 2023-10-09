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

    /**
     * @psalm-param CalculationByMonthType[] $entities
     */
    protected function doRender(array $entities): bool
    {
        $this->SetTitle($this->transChart('title_by_month'));

        $this->AddPage();
        $table = $this->createTable();

        foreach ($entities as $entity) {
            $table->startRow()->addValues(
                $this->formatDate($entity['date']),
                FormatUtils::formatInt($entity['count']),
                FormatUtils::formatInt($entity['items']),
                FormatUtils::formatInt($entity['total'] - $entity['items']),
                $this->formatPercent($entity['margin']),
                FormatUtils::formatInt($entity['total'])
            )->endRow();
        }

        // total
        $count = $this->sum($entities, 'count');
        $items = $this->sum($entities, 'items');
        $total = $this->sum($entities, 'total');
        $net = $total - $items;
        $margin = 1.0 + $this->safeDivide($net, $items);

        $table->startHeaderRow()->addValues(
            $this->transChart('fields.total'),
            FormatUtils::formatInt($count),
            FormatUtils::formatInt($items),
            FormatUtils::formatInt($net),
            $this->formatPercent($margin, true),
            FormatUtils::formatInt($total)
        )->endRow();

        return true;
    }

    private function createTable(): PdfTableBuilder
    {
        $columns = [
            PdfColumn::left($this->transChart('fields.month'), 20),
            PdfColumn::right($this->transChart('fields.count'), 25, true),
            PdfColumn::right($this->transChart('fields.net'), 25, true),
            PdfColumn::right($this->transChart('fields.margin_amount'), 25, true),
            PdfColumn::right($this->transChart('fields.margin_percent'), 20, true),
            PdfColumn::right($this->transChart('fields.total'), 25, true),
        ];

        return PdfTableBuilder::instance($this)
            ->addColumns(...$columns)
            ->outputHeaders();
    }

    private function formatDate(\DateTimeInterface $date): string
    {
        return \ucfirst(FormatUtils::formatDate(date: $date, pattern: 'MMMM Y'));
    }

    private function formatPercent(float $value, bool $bold = false): PdfCell
    {
        $cell = new PdfCell(FormatUtils::formatPercent($value, false));
        $style = $bold ? PdfStyle::getHeaderStyle() : PdfStyle::getCellStyle();
        if ($this->isMinMargin($value)) {
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
