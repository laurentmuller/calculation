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

use App\Repository\CalculationRepository;
use App\Service\ApplicationService;
use App\Traits\ArrayTrait;
use App\Utils\FormatUtils;
use Laminas\Json\Expr;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Chart to display calculations by months.
 *
 * @psalm-import-type CalculationByMonthType from CalculationRepository
 */
class MonthChart extends AbstractHighchart
{
    use ArrayTrait;

    private const TEMPLATE_NAME = 'chart/_month_tooltip.js.twig';

    public function __construct(
        ApplicationService $application,
        private readonly CalculationRepository $repository,
        private readonly UrlGeneratorInterface $generator,
        private readonly Environment $twig,
    ) {
        parent::__construct($application);
    }

    /**
     * Generate the chart data.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Exception
     */
    public function generate(int $months): array
    {
        $allowedMonths = $this->getAllowedMonths();
        $months = $this->checkMonth($months, $allowedMonths);
        $series = $this->repository->getByMonth($months);
        $categories = $this->getCategories($series);

        $this->setType(self::TYPE_COLUMN)
            ->hideTitle()
            ->setPlotOptions()
            ->setLegendOptions()
            ->setTooltipOptions()
            ->setXAxis($categories)
            ->setYAxis()
            ->setSeries($series);

        return [
            'chart' => $this,
            'data' => $series,
            'months' => $months,
            'totals' => $this->getTotals($series),
            'allowed_months' => $allowedMonths,
            'min_margin' => $this->getMinMargin(),
        ];
    }

    protected function setTooltipOptions(): static
    {
        parent::setTooltipOptions();
        $this->tooltip->merge([
            'shared' => true,
            'useHTML' => true,
            'formatter' => $this->createTemplateExpression($this->twig, self::TEMPLATE_NAME),
        ]);

        return $this;
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

    private function formatDate(\DateTimeInterface $date): string
    {
        return FormatUtils::formatDate($date, \IntlDateFormatter::NONE, 'MMMM Y');
    }

    /**
     * @return int[]
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getAllowedMonths(): array
    {
        $step = 6;
        $maxMonths = $this->repository->countDistinctMonths();
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
     * @param CalculationByMonthType[] $series
     *
     * @return int[]
     */
    private function getCategories(array $series): array
    {
        return \array_map(static fn (array $item): int => $item['date']->getTimestamp() * 1000, $series);
    }

    private function getClickExpression(): Expr
    {
        return self::createExpression('function() {location.href = this.url;}');
    }

    private function getFormatterExpression(): Expr
    {
        return self::createExpression('function() {return Highcharts.numberFormat(this.value, 0);}');
    }

    /**
     * Only y and url values are returned.
     *
     * @param CalculationByMonthType[] $series
     */
    private function getItemsSeries(array $series): array
    {
        return \array_map(fn (array $item): array => [
            'y' => $item['items'],
            'url' => $this->getURL($item['date']),
        ], $series);
    }

    private function getMarginColor(float $value): string
    {
        $minMargin = $this->getMinMargin();
        if (!$this->isFloatZero($value) && $value < $minMargin) {
            return 'var(--bs-danger)';
        }

        return 'inherit';
    }

    /**
     * The y value, the url and all data needed by the custom tooltip are returned.
     *
     * @param CalculationByMonthType[] $series
     */
    private function getMarginsSeries(array $series): array
    {
        return \array_map(fn (array $item): array => [
            'y' => $item['margin_amount'],
            'date' => $this->formatDate($item['date']),
            'calculations' => FormatUtils::formatInt($item['count']),
            'net_amount' => FormatUtils::formatInt($item['items']),
            'margin_percent' => FormatUtils::formatPercent($item['margin_percent']),
            'margin_amount' => FormatUtils::formatInt($item['margin_amount']),
            'margin_color' => $this->getMarginColor($item['margin_percent']),
            'total_amount' => FormatUtils::formatInt($item['total']),
            'url' => $this->getURL($item['date']),
        ], $series);
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
                'calculations',
                'net_amount',
                'margin_percent',
                'margin_amount',
                'margin_color',
                'total_amount',
                'url',
            ],
        ];
    }

    /**
     * @param CalculationByMonthType[] $series
     */
    private function getTotals(array $series): array
    {
        $count = $this->getColumnSum($series, 'count');
        $total = $this->getColumnSum($series, 'total');
        $items = $this->getColumnSum($series, 'items');
        $margin_percent = $this->round($this->safeDivide($total, $items), 4);
        $margin_amount = $total - $items;

        return [
            'count' => $count,
            'items' => $items,
            'margin_percent' => $margin_percent,
            'margin_amount' => $margin_amount,
            'total' => $total,
        ];
    }

    private function getURL(\DateTimeInterface $date): string
    {
        return $this->generator->generate('calculation_table', [
            'search' => $date->format('m.Y'),
        ]);
    }

    private function setLegendOptions(): self
    {
        $this->legend->merge(['enabled' => false]);

        return $this;
    }

    private function setPlotOptions(): self
    {
        $this->plotOptions->merge([
            'series' => $this->getSeriesOptions(),
        ]);

        return $this;
    }

    /**
     * @param CalculationByMonthType[] $series
     */
    private function setSeries(array $series): void
    {
        $this->series->merge([
            [
                'name' => $this->trans('calculation.fields.margin'),
                'data' => $this->getMarginsSeries($series),
                'color' => 'darkred',
            ],
            [
                'name' => $this->trans('calculationgroup.fields.amount'),
                'data' => $this->getItemsSeries($series),
                'color' => 'darkgreen',
            ],
        ]);
    }

    private function setXAxis(array $categories): self
    {
        $this->xAxis->merge([
            'type' => 'datetime',
            'categories' => $categories,
            'labels' => [
                'format' => '{value:%b %Y}',
            ],
        ]);

        return $this;
    }

    private function setYAxis(): self
    {
        $this->yAxis->merge([
            'labels' => [
                'formatter' => $this->getFormatterExpression(),
            ],
            'title' => [
                'text' => null,
            ],
        ]);

        return $this;
    }
}
