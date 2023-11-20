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
        $data = $this->repository->getByMonth($months);
        $dateValues = $this->getDateValues($data);
        $countValues = $this->getCountValues($data);
        $itemValues = $this->getItemValues($data);
        $sumValues = $this->getSumValues($data);
        $marginPercents = $this->getMarginPercents($data);
        $marginAmounts = $this->getMarginAmounts($data);
        $series = $this->getSeries($data);
        $yAxis = $this->getYaxis();
        $xAxis = $this->getXAxis($dateValues);

        $data = [];
        foreach ($dateValues as $index => $date) {
            $data[] = [
                'date' => (int) ($date / 1_000),
                'count' => $countValues[$index],
                'sum' => $sumValues[$index],
                'items' => $itemValues[$index],
                'margin_amount' => $marginAmounts[$index],
                'margin_percent' => $marginPercents[$index],
            ];
        }
        $count = \array_sum($countValues);
        $total = (float) \array_sum($sumValues);
        $items = (float) \array_sum($itemValues);
        $marginAmount = $total - $items;
        $marginPercent = $this->safeDivide($total, $items);

        $this->setType(self::TYPE_COLUMN)
            ->hideTitle()
            ->hideLegend()
            ->setPlotOptions()
            ->setTooltipOptions()
            ->setXAxis($xAxis)
            ->setYAxis($yAxis)
            ->setSeries($series);

        return [
            'chart' => $this,
            'data' => $data,
            'count' => $count,
            'items' => $items,
            'months' => $months,
            'margin_percent' => $marginPercent,
            'margin_amount' => $marginAmount,
            'total' => $total,
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
        return \ucfirst(FormatUtils::formatDate($date, pattern: 'MMMM Y'));
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
     * @param CalculationByMonthType[] $data
     *
     * @return int[]
     */
    private function getCountValues(array $data): array
    {
        return \array_map(fn (array $item): int => $item['count'], $data);
    }

    /**
     * @param CalculationByMonthType[] $data
     *
     * @return int[]
     */
    private function getDateValues(array $data): array
    {
        return \array_map(static fn (array $item): int => $item['date']->getTimestamp() * 1000, $data);
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
     * @param CalculationByMonthType[] $data
     *
     * @return float[]
     */
    private function getItemValues(array $data): array
    {
        return \array_map(fn (array $item): float => $item['items'], $data);
    }

    /**
     * @param CalculationByMonthType[] $data
     *
     * @return float[]
     */
    private function getMarginAmounts(array $data): array
    {
        return \array_map(static fn (array $item): float => $item['total'] - $item['items'], $data);
    }

    /**
     * @param CalculationByMonthType[] $data
     *
     * @return float[]
     */
    private function getMarginPercents(array $data): array
    {
        return \array_map(static fn (array $item): float => $item['margin_percent'], $data);
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
                'margin_percent' => FormatUtils::formatPercent($item['margin_percent'], false),
                'margin_amount' => FormatUtils::formatInt($item['margin_amount']),
                'total_amount' => FormatUtils::formatInt($item['total']),
            ];
        }, $data);
    }

    /**
     * @param CalculationByMonthType[] $data
     */
    private function getSeries(array $data): array
    {
        return [
            [
                'name' => $this->transChart('fields.margin'),
                'data' => $this->getMarginsSeries($data),
                'color' => 'darkred',
            ],
            [
                'name' => $this->transChart('fields.net'),
                'data' => $this->getItemsSeries($data),
                'color' => 'darkgreen',
            ],
        ];
    }

    /**
     * @param CalculationByMonthType[] $data
     *
     * @return float[]
     */
    private function getSumValues(array $data): array
    {
        return \array_map(static fn (array $item): float => $item['total'], $data);
    }

    private function getXAxis(array $dates): array
    {
        return [
            'type' => 'datetime',
            'categories' => $dates,
            'lineColor' => $this->getBorderColor(),
            'labels' => [
                'format' => '{value:%b %Y}',
                'style' => $this->getFontStyle('0.875rem'),
            ],
        ];
    }

    private function getYaxis(): array
    {
        $function = <<<JAVA_SCRIPT
            function() {
               return Highcharts.numberFormat(this.value, 0);
            }
            JAVA_SCRIPT;
        $formatter = $this->createExpression($function);

        return [
            [
                'gridLineColor' => $this->getBorderColor(),
                'labels' => [
                    'formatter' => $formatter,
                    'style' => $this->getFontStyle('0.875rem'),
                ],
                'title' => [
                    'text' => null,
                ],
            ],
        ];
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
}
