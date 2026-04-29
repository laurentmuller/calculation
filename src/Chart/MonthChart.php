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

use App\Entity\Calculation;
use App\Model\CalculationsMonthItem;
use App\Model\CalculationsTotal;
use App\Pdf\Html\HtmlColorName;
use App\Repository\CalculationRepository;
use App\Utils\FormatUtils;
use HighchartsBundle\Highcharts\ChartExpression;

/**
 * Chart to display calculations by months.
 */
class MonthChart extends AbstractHighchart
{
    /** The HTML color name for amounts. */
    public const HtmlColorName COLOR_AMOUNT = HtmlColorName::SEA_GREEN;

    /** The HTML color name for margins. */
    public const HtmlColorName COLOR_MARGIN = HtmlColorName::INDIAN_RED;

    /**
     * Generate the chart data.
     *
     * @return array{
     *     chart: MonthChart,
     *     data: CalculationsMonthItem[],
     *     months: int,
     *     totals: CalculationsTotal,
     *     allowedMonths: int[],
     *     minMargin: float}
     */
    public function generate(int $months): array
    {
        $allowedMonths = $this->getAllowedMonths();
        $months = $this->checkMonth($months, $allowedMonths);
        $calculationMonths = $this->getRepository()->getByMonth($months);
        $items = $calculationMonths->items;

        $this->setType(ChartType::TYPE_COLUMN)
            ->setSeries($items)
            ->setXAxis($items)
            ->setPlotOptions()
            ->setYAxis();

        return [
            'chart' => $this,
            'data' => $items,
            'months' => $months,
            'totals' => $calculationMonths->total,
            'allowedMonths' => $allowedMonths,
            'minMargin' => $this->getMinMargin(),
        ];
    }

    #[\Override]
    protected function setLegendOptions(): static
    {
        $this->legend->merge(['enabled' => false]);

        return parent::setLegendOptions();
    }

    #[\Override]
    protected function setTooltipOptions(): static
    {
        $this->tooltip->merge(['formatter' => $this->getTooltipExpression()]);

        return parent::setTooltipOptions();
    }

    /**
     * @param int[] $allowedMonths
     */
    private function checkMonth(int $count, array $allowedMonths): int
    {
        if (!\in_array($count, $allowedMonths, true)) {
            return \in_array(6, $allowedMonths, true) ? 6 : $allowedMonths[0];
        }

        return \max(1, $count);
    }

    /**
     * @return int[]
     */
    private function getAllowedMonths(): array
    {
        $step = 6;
        $maxMonths = $this->getRepository()->countDistinctMonths();
        if ($maxMonths <= $step) {
            return [$maxMonths];
        }

        if ($maxMonths % $step > 0) {
            $delta = $step % $maxMonths;
            $maxMonths += $delta;
        }

        return \range($step, $maxMonths, $step);
    }

    /**
     * @param CalculationsMonthItem[] $items
     *
     * @return int[]
     */
    private function getCategories(array $items): array
    {
        return \array_map(static fn (CalculationsMonthItem $item): int => $item->getMilliseconds(), $items);
    }

    private function getFormatterExpression(): ChartExpression
    {
        return ChartExpression::instance('function() {return Highcharts.numberFormat(this.value, 0);}');
    }

    /**
     * Only y and url values are returned.
     *
     * @param CalculationsMonthItem[] $items
     */
    private function getItemsSeries(array $items): array
    {
        return \array_map(fn (CalculationsMonthItem $item): array => [
            'y' => $item->items,
            'url' => $this->getURL($item),
        ], $items);
    }

    /**
     * The y value, the url, and all data needed by the custom tooltip are returned.
     *
     * @param CalculationsMonthItem[] $items
     */
    private function getMarginsSeries(array $items): array
    {
        return \array_map(fn (CalculationsMonthItem $item): array => [
            'y' => $item->marginAmount,
            'date' => $item->formatDate(),
            'count' => FormatUtils::formatInt($item->count),
            'items' => FormatUtils::formatInt($item->items),
            'marginAmount' => FormatUtils::formatInt($item->marginAmount),
            'marginPercent' => FormatUtils::formatPercent($item->marginPercent),
            'marginClass' => $this->getMarginClass($item->marginPercent),
            'totalAmount' => FormatUtils::formatInt($item->total),
            'url' => $this->getURL($item),
        ], $items);
    }

    private function getRepository(): CalculationRepository
    {
        return $this->manager->getRepository(Calculation::class);
    }

    private function getSeriesOptions(): array
    {
        return [
            'pointPadding' => 0,
            'cursor' => 'pointer',
            'stacking' => 'normal',
            'borderRadius' => ['radius' => 0],
            'borderColor' => $this->getBorderColor(),
            'point' => [
                'events' => [
                    'click' => $this->getClickExpression(),
                ],
            ],
            'keys' => [
                'y',
                'date',
                'count',
                'items',
                'marginAmount',
                'marginPercent',
                'marginColor',
                'totalAmount',
                'url',
            ],
        ];
    }

    private function getURL(CalculationsMonthItem $item): string
    {
        return $this->generator->generate('calculation_index', [
            'search' => $item->getSearchDate(),
        ]);
    }

    private function setPlotOptions(): self
    {
        $this->plotOptions->merge(['series' => $this->getSeriesOptions()]);

        return $this;
    }

    /**
     * @phpstan-param CalculationsMonthItem[] $items
     */
    private function setSeries(array $items): self
    {
        $this->series->merge([
            [
                'name' => $this->trans('calculation.fields.margin'),
                'data' => $this->getMarginsSeries($items),
                'color' => self::COLOR_MARGIN->value,
            ],
            [
                'name' => $this->trans('calculationgroup.fields.amount'),
                'data' => $this->getItemsSeries($items),
                'color' => self::COLOR_AMOUNT->value,
            ],
        ]);

        return $this;
    }

    /**
     * @param CalculationsMonthItem[] $items
     */
    private function setXAxis(array $items): self
    {
        $categories = $this->getCategories($items);
        $this->xAxis->merge([
            'type' => 'datetime',
            'categories' => $categories,
            'labels' => ['format' => '{value:%b %Y}'],
        ]);

        return $this;
    }

    private function setYAxis(): void
    {
        $this->yAxis->merge([
            'labels' => [
                'formatter' => $this->getFormatterExpression(),
            ],
            'title' => [
                'text' => null,
            ],
        ]);
    }
}
