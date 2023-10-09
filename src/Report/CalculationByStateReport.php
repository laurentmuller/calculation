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
use App\Repository\CalculationStateRepository;
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
    /**
     * @psalm-param  QueryCalculationType[] $entities
     */
    protected function doRender(array $entities): bool
    {
        $this->SetTitle($this->transChart('title_by_state'));

        $this->AddPage();
        $table = $this->createTable();

        foreach ($entities as $entity) {
            $table->startRow()
                ->add($entity['code'])
                ->add(FormatUtils::formatInt($entity['count']))
                ->add($this->formatPercent($entity['percentCalculation']))
                ->add(FormatUtils::formatInt($entity['items']))
                ->add(FormatUtils::formatInt($entity['marginAmount']))
                ->add(FormatUtils::formatPercent($entity['margin']))
                ->add(FormatUtils::formatInt($entity['total']))
                ->add($this->formatPercent($entity['percentAmount']))
                ->endRow();
        }

        // total
        $count = $this->sum($entities, 'count');
        $items = $this->sum($entities, 'items');
        $marginAmount = $this->sum($entities, 'marginAmount');
        $total = $this->sum($entities, 'total');
        $net = $total - $items;
        $margin = 1.0 + $net / $items;

        $table->startHeaderRow()
            ->add($this->transChart('fields.total'))
            ->add(FormatUtils::formatInt($count))
            ->add($this->formatPercent(1.0))
            ->add(FormatUtils::formatInt($items))
            ->add(FormatUtils::formatInt($marginAmount))
            ->add($this->formatPercent($margin, 0))
            ->add(FormatUtils::formatInt($total))
            ->add($this->formatPercent(1.0))
            ->endRow();

        return true;
    }

    private function createTable(): PdfTableBuilder
    {
        $columns = [
            PdfColumn::left($this->transChart('fields.state'), 20),
            PdfColumn::right($this->transChart('fields.count'), 25, true),
            PdfColumn::right($this->transChart('fields.percent'), 25, true),
            PdfColumn::right($this->transChart('fields.net'), 20, true),
            PdfColumn::right($this->transChart('fields.margin_amount'), 20, true),
            PdfColumn::right($this->transChart('fields.margin_percent'), 20, true),
            PdfColumn::right($this->transChart('fields.total'), 20, true),
            PdfColumn::right($this->transChart('fields.percent'), 25, true),
        ];

        return PdfTableBuilder::instance($this)
            ->addColumns(...$columns)
            ->outputHeaders();
    }

    private function formatPercent(float $value, int $decimals = 1): string
    {
        return FormatUtils::formatPercent($value, true, $decimals, \NumberFormatter::ROUND_HALFEVEN);
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
