<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Chart;

use App\Repository\CalculationRepository;
use App\Service\ApplicationService;
use App\Service\ThemeService;
use App\Traits\MathTrait;
use App\Traits\TranslatorTrait;
use Laminas\Json\Expr;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Chart to display calculations by month.
 *
 * @author Laurent Muller
 */
class MonthChart extends BaseChart
{
    use MathTrait;
    use TranslatorTrait;

    private ApplicationService $application;
    private CalculationRepository $repository;
    private ThemeService $service;
    private string $url;

    /**
     * Contructor.
     */
    public function __construct(ApplicationService $application, CalculationRepository $repository, ThemeService $service, TranslatorInterface $translator, UrlGeneratorInterface $generator)
    {
        parent::__construct();
        $this->initLangOptions();
        $this->application = $application;
        $this->repository = $repository;
        $this->service = $service;
        $this->url = $generator->generate('calculation_table');
        $this->setTranslator($translator);
    }

    public function generate(int $months): array
    {
        // get values
        $allowedMonths = $this->getAllowedMonths();
        $months = $this->checkMonth($months, $allowedMonths);
        $data = $this->repository->getByMonth($months);
        $dates = $this->getDates($data);
        $countData = $this->getCount($data);
        $itemsData = $this->getItems($data);
        $marginsData = $this->getMargins($data);
        $sumData = $this->getSums($data);
        $marginsPercent = $this->getMarginPercents($data);
        $marginsAmount = $this->getMarginAmounts($data);

        // series
        $series = $this->getSeries($marginsData, $itemsData);

        // axes
        $color = $this->getForeground();
        $yAxis = $this->getYaxis($color);
        $xAxis = $this->getXaxis($color, $dates);

        // tootltip formatter
        $formatter = $this->getFormatterExpression();

        // click event
        $click = $this->getClickExpression();

        // update
        $this->setType(self::TYPE_COLUMN)
            ->hideTitle()
            ->hideLegend()
            ->xAxis($xAxis)
            ->yAxis($yAxis)
            ->series($series);

        // @phpstan-ignore-next-line
        $this->plotOptions->series([
            'stacking' => 'normal',
            'cursor' => 'pointer',
            'point' => [
                'events' => [
                    'click' => $click,
                ],
            ],
        ]);

        // @phpstan-ignore-next-line
        $this->tooltip->useHTML(true)
            ->formatter($formatter);

        // data
        $data = [];
        foreach ($dates as $index => $date) {
            $data[] = [
                'date' => ($date / 1000),
                'count' => $countData[$index],
                'sum' => $sumData[$index],
                'items' => $itemsData[$index],
                'marginAmount' => $marginsAmount[$index],
                'marginPercent' => $marginsPercent[$index],
            ];
        }

        $count = \array_sum($countData);
        $items = \array_sum($itemsData);
        $total = \array_sum($sumData);
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
            'min_margin' => $this->application->getMinMargin(),
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
     */
    private function getAllowedMonths(): array
    {
        $values = [4, 6, 12, 18, 24];
        $maxMonths = $this->repository->countDistinctMonths();

        if (\end($values) <= $maxMonths) {
            return $values;
        }

        foreach ($values as $index => $value) {
            if ($value >= $maxMonths) {
                return \array_slice($values, 0, $index + 1);
            }
        }

        // must never been here!
        return $values;
    }

    private function getClickExpression(): Expr
    {
        $function = <<<EOF
            function() {
                const href = "{$this->url}?search=" + Highcharts.dateFormat("%m.%Y", this.category);
                location.href = href;
            }
            EOF;

        return new Expr($function);
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
    private function getCount(array $data): array
    {
        return \array_map(function (array $item): int {
            return $item['count'];
        }, $data);
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
    private function getDates(array $data): array
    {
        return \array_map(function (array $item): int {
            return (int) $item['date']->getTimestamp() * 1000;
        }, $data);
    }

    /**
     * Gets the chart's label foreground.
     */
    private function getForeground(): string
    {
        return $this->service->isDarkTheme() ? 'white' : 'black';
    }

    private function getFormatterExpression(): Expr
    {
        $function = <<<EOF
            function () {
                var date = Highcharts.dateFormat("%B %Y", this.x);
                var name = this.series.name;
                var yValue = Highcharts.numberFormat(this.y, 0);
                var totalValue = Highcharts.numberFormat(this.total, 0);
                var html = "<table>";
                html += "<tr><th colspan=\"3\">" + date + "</th></tr>";
                html += "<tr><td>" +  name + "</td><td>:</td><td class=\"text-currency\">" + yValue + "</td></tr>";
                html += "<tr><td>Total</td><td>:</td><td class=\"text-currency\">" + totalValue + "</td></tr>";
                html += "</table>";
                return html;
            }
            EOF;

        return new Expr($function);
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
    private function getItems(array $data): array
    {
        return \array_map(function (array $item): float {
            return $item['items'];
        }, $data);
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
        return \array_map(function (array $item): float {
            return $item['total'] - $item['items'];
        }, $data);
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
        return \array_map(function (array $item): float {
            return $item['margin'];
        }, $data);
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
        return \array_map(function (array $item): float {
            return $item['total'] - $item['items'];
        }, $data);
    }

    private function getSeries(array $marginsData, array $itemsData): array
    {
        return [
            [
                'name' => $this->trans('fields.margin', [], 'chart'),
                'data' => $marginsData,
                'color' => 'darkred',
            ],
            [
                'name' => $this->trans('fields.net', [], 'chart'),
                'data' => $itemsData,
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
    private function getSums(array $data): array
    {
        return \array_map(function (array $item): float {
            return $item['total'];
        }, $data);
    }

    private function getXaxis(string $color, array $dates): array
    {
        return [
            'type' => 'datetime',
            'categories' => $dates,
            'labels' => [
                'format' => '{value:%b %Y}',
                'style' => [
                    'color' => $color,
                    'fontSize' => '12px',
                ],
            ],
        ];
    }

    private function getYaxis(string $color): array
    {
        return [
            [
                'labels' => [
                    'formatter' => new Expr('function () { return Highcharts.numberFormat(this.value, 0) }'),
                    'style' => [
                        'color' => $color,
                        'fontSize' => '12px',
                    ],
                ],
                'title' => [
                    'text' => null,
                ],
            ],
        ];
    }
}
