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
use App\Utils\FormatUtils;
use Laminas\Json\Expr;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Chart to display calculations by month.
 *
 * @psalm-import-type CalculationByMonthType from CalculationRepository
 */
class MonthChart extends AbstractHighchart
{
    private const TEMPLATE_NAME = 'chart/chart_month_tooltip.js.twig';

    private readonly string $url;

    public function __construct(
        ApplicationService $application,
        private readonly CalculationRepository $repository,
        UrlGeneratorInterface $generator,
        private readonly Environment $twig,
    ) {
        parent::__construct($application);
        $this->url = $generator->generate('calculation_table');
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
            'allowed_months' => $allowedMonths,
            'min_margin' => $this->getMinMargin(),
            'totals' => $this->getTotals($series),
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
        $function = <<<JAVA_SCRIPT
            function() {
                const href = "$this->url?search=" + Highcharts.dateFormat("%m.%Y", this.category);
                location.href = href;
            }
            JAVA_SCRIPT;

        return $this->createExpression($function);
    }

    /**
     * Only y value is returned.
     *
     * @param CalculationByMonthType[] $data
     */
    private function getItemsSeries(array $data): array
    {
        return \array_map(static fn (array $item): array => ['y' => $item['items']], $data);
    }

    /**
     * The y value and all data needed by custom tooltip are returned.
     *
     * @param CalculationByMonthType[] $data
     */
    private function getMarginsSeries(array $data): array
    {
        return \array_map(function (array $item): array {
            return [
                'y' => $item['total'] - $item['items'],
                'date' => $this->formatDate($item['date']),
                'calculations' => FormatUtils::formatInt($item['count']),
                'net_amount' => FormatUtils::formatInt($item['items']),
                'margin_percent' => FormatUtils::formatPercent($item['margin_percent']),
                'margin_amount' => FormatUtils::formatInt($item['margin_amount']),
                'total_amount' => FormatUtils::formatInt($item['total']),
            ];
        }, $data);
    }

    /**
     * @param CalculationByMonthType[] $series
     */
    private function getTotals(array $series): array
    {
        $count = \array_sum(\array_column($series, 'count'));
        $total = \array_sum(\array_column($series, 'total'));
        $items = \array_sum(\array_column($series, 'items'));
        $margin_amount = $total - $items;
        $margin_percent = $this->safeDivide($total, $items);

        return [
            'count' => $count,
            'items' => $items,
            'margin_percent' => $margin_percent,
            'margin_amount' => $margin_amount,
            'total' => $total,
        ];
    }

    private function setLegendOptions(): self
    {
        $this->legend->merge([
            'align' => 'right',
            'verticalAlign' => 'top',
            'symbolRadius' => 0,
            'reversed' => true,
        ]);

        return $this;
    }

    private function setPlotOptions(): self
    {
        $this->plotOptions['series'] = [
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
                'total_amount',
            ],
        ];

        return $this;
    }

    /**
     * @param CalculationByMonthType[] $series
     */
    private function setSeries(array $series): void
    {
        $this->series->merge([
            [
                'name' => $this->transChart('fields.margin'),
                'data' => $this->getMarginsSeries($series),
                'color' => 'darkred',
            ],
            [
                'name' => $this->transChart('fields.net'),
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
        $function = <<<JAVA_SCRIPT
            function() {
               return Highcharts.numberFormat(this.value, 0);
            }
            JAVA_SCRIPT;

        $this->yAxis->merge([
            'labels' => [
                'formatter' => $this->createExpression($function),
            ],
            'title' => [
                'text' => null,
            ],
        ]);

        return $this;
    }
}
