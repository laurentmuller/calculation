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
use Laminas\Json\Expr;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Chart to display calculations by month.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MonthChart extends BaseChart
{
    private readonly string $url;

    /**
     * Constructor.
     */
    public function __construct(ApplicationService $application, private readonly CalculationRepository $repository, UrlGeneratorInterface $generator)
    {
        parent::__construct($application);
        $this->url = $generator->generate('calculation_table');
    }

    /**
     * Generate the chart data.
     *
     * @param int $months the number of months to display
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Exception
     */
    public function generate(int $months): array
    {
        // get values
        $allowedMonths = $this->getAllowedMonths();
        $months = $this->checkMonth($months, $allowedMonths);
        $data = $this->repository->getByMonth($months);

        $dateValues = $this->getDateValues($data);
        $countValues = $this->getCountValues($data);
        $itemValues = $this->getItemValues($data);
        $sumValues = $this->getSumValues($data);
        $marginPercents = $this->getMarginPercents($data);
        $marginAmounts = $this->getMarginAmounts($data);

        // series
        $series = $this->getSeries($data);

        // axes
        $yAxis = $this->getYaxis();
        $xAxis = $this->getXAxis($dateValues);

        // update
        $this->setType(self::TYPE_COLUMN)
            ->hideTitle()
            ->hideLegend()
            ->setPlotOptions()
            ->setTooltipOptions()
            ->xAxis($xAxis)
            ->yAxis($yAxis)
            ->series($series);

        // data
        $data = [];
        foreach ($dateValues as $index => $date) {
            $data[] = [
                'date' => ($date / 1000),
                'count' => $countValues[$index],
                'sum' => $sumValues[$index],
                'items' => $itemValues[$index],
                'marginAmount' => $marginAmounts[$index],
                'marginPercent' => $marginPercents[$index],
            ];
        }

        $count = \array_sum($countValues);
        $total = \array_sum($sumValues);
        $items = \array_sum($itemValues);
        $marginAmount = $total - $items;
        $marginPercent = $this->safeDivide($total, $items);

        return [
            'chart' => $this,
            'data' => $data,
            'count' => $count,
            'items' => $items,
            'months' => $months,
            'marginPercent' => $marginPercent,
            'marginAmount' => $marginAmount,
            'total' => $total,
            'allowed_months' => $allowedMonths,
            'min_margin' => $this->getMinMargin(),
        ];
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
     * Gets the allowed months to display.
     *
     * @return int[]
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getAllowedMonths(): array
    {
        $step = 6;
        $maxMonths = $this->repository->countDistinctMonths();
        if ($maxMonths % $step > 0) {
            $maxMonths += $step;
        }

        return \range($step, $maxMonths, $step);
    }

    private function getClickExpression(): Expr
    {
        $function = <<<FUNCTION
            function() {
                const href = "$this->url?search=" + Highcharts.dateFormat("%m.%Y", this.category);
                location.href = href;
            }
            FUNCTION;

        return $this->createExpression($function);
    }

    /**
     * @param array<array{
     *      count: int,
     *      items: float,
     *      total: float,
     *      year: int,
     *      month: int,
     *      margin: float,
     *      date: \DateTimeInterface}> $data
     *
     * @return int[]
     */
    private function getCountValues(array $data): array
    {
        return \array_map(fn (array $item): int => $item['count'], $data);
    }

    /**
     * @param array<array{
     *      count: int,
     *      items: float,
     *      total: float,
     *      year: int,
     *      month: int,
     *      margin: float,
     *      date: \DateTimeInterface}> $data
     *
     * @return int[]
     */
    private function getDateValues(array $data): array
    {
        return \array_map(fn (array $item): int => (int) $item['date']->getTimestamp() * 1000, $data);
    }

    private function getFormatterExpression(): Expr
    {
        $month = $this->transChart('fields.month');
        $count = $this->transChart('fields.count');
        $amount = $this->transChart('fields.net');
        $total = $this->transChart('fields.total');
        $marginAmount = $this->transChart('fields.margin_amount');
        $marginPercent = $this->transChart('fields.margin_percent');

        $function = <<<FUNCTION
            function () {
                const ptMargin = this.points[0];
                const ptAmount = this.points[1];
                var html = '<table class="m-1">';

                // month
                html += '<tr class="border-bottom border-dark"><th>$month</th><th>&nbsp:&nbsp</th class="text-calculation"><th>' + Highcharts.dateFormat("%B %Y", this.x) + '</th></tr>';

                // count (calculations)
                let value = Highcharts.numberFormat(ptAmount.point.custom.count, 0);
                html += '<tr><td class="text-category">$count</td><td>&nbsp:&nbsp</td><td class="text-calculation">' + value + '</td></tr>';

                // amount
                let color = 'color:' + ptAmount.color + ';';
                value = Highcharts.numberFormat(ptAmount.y, 0);
                html += '<tr><td class="text-category" style="' + color + '">$amount</td><td>&nbsp:&nbsp</td><td class="text-calculation">' + value + '</td></tr>';

                // margin amount
                color = 'color:' + ptMargin.color + ';';
                value = Highcharts.numberFormat(ptMargin.y, 0);
                html += '<tr><td class="text-category" style="' + color + '">$marginAmount</td><td>&nbsp:&nbsp</td><td class="text-calculation">' + value + '</td></tr>';

                // margin percent
                value =  Highcharts.numberFormat(100 + Math.floor(ptMargin.y * 100 / ptAmount.y), 0);
                html += '<tr><td class="text-category">$marginPercent</td><td>&nbsp:&nbsp</td><td class="text-calculation">' + value + '</td></tr>';

                // total
                value = Highcharts.numberFormat(ptAmount.y + ptMargin.y, 0);
                html += '<tr class="border-top border-dark"><th>$total</th><th>&nbsp:&nbsp</th><th class="text-calculation">' + value + '</th></tr>';

                html += '</table>';
                return html;
            }
            FUNCTION;

        return $this->createExpression($function);
    }

    /**
     * @param array<array{
     *      count: int,
     *      items: float,
     *      total: float,
     *      year: int,
     *      month: int,
     *      margin: float,
     *      date: \DateTimeInterface}> $data
     *
     * @return array<array-key, array{float, int}>
     */
    private function getItemsSeries(array $data): array
    {
        return \array_map(fn (array $item): array => [$item['items'], $item['count']], $data);
    }

    /**
     * @param array<array{
     *      count: int,
     *      items: float,
     *      total: float,
     *      year: int,
     *      month: int,
     *      margin: float,
     *      date: \DateTimeInterface}> $data
     *
     * @return float[]
     */
    private function getItemValues(array $data): array
    {
        return \array_map(fn (array $item): float => $item['items'], $data);
    }

    /**
     * @param array<array{
     *      count: int,
     *      items: float,
     *      total: float,
     *      year: int,
     *      month: int,
     *      margin: float,
     *      date: \DateTimeInterface}> $data
     *
     * @return float[]
     */
    private function getMarginAmounts(array $data): array
    {
        return \array_map(fn (array $item): float => $item['total'] - $item['items'], $data);
    }

    /**
     * @param array<array{
     *      count: int,
     *      items: float,
     *      total: float,
     *      year: int,
     *      month: int,
     *      margin: float,
     *      date: \DateTimeInterface}> $data
     *
     * @return float[]
     */
    private function getMarginPercents(array $data): array
    {
        return \array_map(fn (array $item): float => $item['margin'], $data);
    }

    /**
     * @param array<array{
     *      count: int,
     *      items: float,
     *      total: float,
     *      year: int,
     *      month: int,
     *      margin: float,
     *      date: \DateTimeInterface}> $data
     *
     * @return float[]
     */
    private function getMargins(array $data): array
    {
        return \array_map(fn (array $item): float => $item['total'] - $item['items'], $data);
    }

    /**
     * @param array<array{
     *      count: int,
     *      items: float,
     *      total: float,
     *      year: int,
     *      month: int,
     *      margin: float,
     *      date: \DateTimeInterface}> $data
     *
     * @return array<array-key, array{float, int}>
     */
    private function getMarginsSeries(array $data): array
    {
        return \array_map(fn (array $item): array => [$item['total'] - $item['items'], $item['count']], $data);
    }

    /**
     * @param array<array{
     *      count: int,
     *      items: float,
     *      total: float,
     *      year: int,
     *      month: int,
     *      margin: float,
     *      date: \DateTimeInterface}> $data
     *
     * @return array[]
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
     * @param array<array{
     *      count: int,
     *      items: float,
     *      total: float,
     *      year: int,
     *      month: int,
     *      margin: float,
     *      date: \DateTimeInterface}> $data
     *
     * @return float[]
     */
    private function getSumValues(array $data): array
    {
        return \array_map(fn (array $item): float => $item['total'], $data);
    }

    private function getXAxis(array $dates): array
    {
        return [
            'type' => 'datetime',
            'categories' => $dates,
            'labels' => [
                'format' => '{value:%B %Y}',
                'style' => $this->getFontStyle(12),
            ],
        ];
    }

    private function getYaxis(): array
    {
        $function = <<<FUNCTION
            function() {
               return Highcharts.numberFormat(this.value, 0);
            }
            FUNCTION;
        $formatter = $this->createExpression($function);

        return [
            [
                'labels' => [
                    'formatter' => $formatter,
                    'style' => [
                        'fontSize' => '12px',
                    ],
                ],
                'title' => [
                    'text' => null,
                ],
            ],
        ];
    }

    /**
     * Sets the plot options.
     */
    private function setPlotOptions(): self
    {
        // @phpstan-ignore-next-line
        $this->plotOptions->series([
            'cursor' => 'pointer',
            'stacking' => 'normal',
            'pointPadding' => 0,
            'keys' => ['y', 'custom.count'],
            'point' => [
                'events' => [
                   'click' => $this->getClickExpression(),
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Sets the tooltip options.
     */
    private function setTooltipOptions(): self
    {
        // @phpstan-ignore-next-line
        $this->tooltip
            ->formatter($this->getFormatterExpression())
            ->style($this->getFontStyle(12))
            ->borderColor('rgba(255, 255, 255, 0.125)')
            ->backgroundColor('white')
            ->borderRadius(4)
            ->useHTML(true)
            ->shared(true);

        return $this;
    }
}
