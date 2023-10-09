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

use App\Pdf\PdfColumn;
use App\Pdf\PdfTableBuilder;
use App\Repository\CalculationRepository;
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
    /**
     * @psalm-param CalculationByMonthType[] $entities
     */
    protected function doRender(array $entities): bool
    {
        $this->SetTitle($this->transChart('title_by_month'));

        $this->AddPage();
        $table = $this->createTable();

        foreach ($entities as $entity) {
            $table->startRow()
                ->add($this->formatDate($entity['date']))
                ->add(FormatUtils::formatInt($entity['count']))
                ->add(FormatUtils::formatInt($entity['items']))
                ->add(FormatUtils::formatInt($entity['total'] - $entity['items']))
                ->add(FormatUtils::formatPercent($entity['margin'], false))
                ->add(FormatUtils::formatInt($entity['total']))
                ->endRow();
        }

        // total
        $count = $this->sum($entities, 'count');
        $items = $this->sum($entities, 'items');
        $total = $this->sum($entities, 'total');
        $net = $total - $items;
        $margin = 1.0 + $net / $items;

        $table->startHeaderRow()
            ->add($this->transChart('fields.total'))
            ->add(FormatUtils::formatInt($count))
            ->add(FormatUtils::formatInt($items))
            ->add(FormatUtils::formatInt($net))
            ->add(FormatUtils::formatPercent($margin, false))
            ->add(FormatUtils::formatInt($total))
            ->endRow();

        return true;
    }

    private function createTable(): PdfTableBuilder
    {
        $columns = [
            PdfColumn::left($this->transChart('fields.month'), 20),
            PdfColumn::right($this->transChart('fields.count'), 25, true),
            PdfColumn::right($this->transChart('fields.net'), 25, true),
            PdfColumn::right($this->transChart('fields.margin_amount'), 25, true),
            PdfColumn::right($this->transChart('fields.margin_percent'), 25, true),
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

    private function sum(array $entities, string $key): float
    {
        return \array_sum(\array_column($entities, $key));
    }

    private function transChart(string $key): string
    {
        return $this->trans($key, [], 'chart');
    }
}
