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

namespace App\Chart;

use App\Entity\CalculationState;
use App\Model\StateChartData;
use App\Model\StateChartDataItem;
use App\Table\CalculationTable;
use App\Utils\FormatUtils;
use HighchartsBundle\Highcharts\ChartExpression;

/**
 * Chart to display calculations by state.
 */
class StateChart extends AbstractHighchart
{
    /**
     * Generate the chart data.
     *
     * @return array{
     *     chart: StateChart,
     *     data: StateChartData,
     *     minMargin: float}
     */
    public function generate(): array
    {
        $data = $this->getStateChartData();
        $this->setType(ChartType::TYPE_PIE)
            ->setColors($data->items)
            ->setSeries($data->items)
            ->setPlotOptions();

        return [
            'chart' => $this,
            'data' => $data,
            'minMargin' => $this->getMinMargin(),
        ];
    }

    #[\Override]
    protected function setLegendOptions(): static
    {
        $this->legend->merge([
            'events' => [
                'itemClick' => ChartExpression::instance('function(e) {itemClicked(e);}'),
            ],
        ]);

        return parent::setLegendOptions();
    }

    #[\Override]
    protected function setTooltipOptions(): static
    {
        $this->tooltip->merge(['formatter' => $this->getTooltipExpression()]);

        return parent::setTooltipOptions();
    }

    private function formatPercent(float $value): string
    {
        return FormatUtils::formatPercent($value, true, 2, \NumberFormatter::ROUND_HALFEVEN);
    }

    private function getPieOptions(): array
    {
        return [
            'cursor' => 'pointer',
            'showInLegend' => true,
            'allowPointSelect' => true,
            'dataLabels' => ['enabled' => false],
            'borderRadius' => ['radius' => 0],
            'borderColor' => $this->getBorderColor(),
            'point' => [
                'events' => ['click' => $this->getClickExpression()],
            ],
        ];
    }

    private function getSeriesOptions(): array
    {
        return [
            'keys' => [
                'y',
                'id',
                'name',
                'count',
                'calculationsPercent',
                'items',
                'marginAmount',
                'marginPercent',
                'marginColor',
                'totalPercent',
                'totalAmount',
                'url',
            ],
        ];
    }

    private function getStateChartData(): StateChartData
    {
        return $this->manager->getRepository(CalculationState::class)
            ->getStateChartData();
    }

    private function getURL(int $id): string
    {
        return $this->generator->generate('calculation_index', [
            CalculationTable::PARAM_STATE => $id,
        ]);
    }

    /**
     * @param StateChartDataItem[] $items
     */
    private function mapItems(array $items): array
    {
        return \array_map(fn (StateChartDataItem $entry): array => [
            'y' => $entry->total,
            'id' => $entry->id,
            'name' => $entry->code,
            'count' => FormatUtils::formatInt($entry->count),
            'calculationsPercent' => $this->formatPercent($entry->calculationsPercent),
            'items' => FormatUtils::formatInt($entry->items),
            'marginPercent' => FormatUtils::formatPercent($entry->marginPercent),
            'marginAmount' => FormatUtils::formatInt($entry->marginAmount),
            'marginClass' => $this->getMarginClass($entry->marginPercent),
            'totalAmount' => FormatUtils::formatInt($entry->total),
            'totalPercent' => $this->formatPercent($entry->totalPercent),
            'url' => $this->getURL($entry->id),
        ], $items);
    }

    /**
     * @param StateChartDataItem[] $items
     */
    private function setColors(array $items): self
    {
        $this->colors = \array_map(static fn (StateChartDataItem $state): string => $state->color, $items);

        return $this;
    }

    private function setPlotOptions(): void
    {
        $this->plotOptions->merge([
            'pie' => $this->getPieOptions(),
            'series' => $this->getSeriesOptions(),
        ]);
    }

    /**
     * @param StateChartDataItem[] $items
     */
    private function setSeries(array $items): self
    {
        $this->series->merge([
            [
                'name' => $this->trans('chart.state.title'),
                'data' => $this->mapItems($items),
            ],
        ]);

        return $this;
    }
}
