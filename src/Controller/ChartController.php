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

namespace App\Controller;

use App\Chart\Basechart;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Service\ThemeService;
use App\Traits\MathTrait;
use Laminas\Json\Expr;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for charts.
 *
 * @author Laurent Muller
 *
 * @Route("/chart")
 * @IsGranted("ROLE_USER")
 */
class ChartController extends AbstractController
{
    use MathTrait;

    /**
     * Gets the calculations by month.
     *
     * @Route("/month/{count}", name="chart_by_month", requirements={"count" = "\d+" })
     */
    public function byMonth(int $count = 12, CalculationRepository $repository, ThemeService $service): Response
    {
        $tabular = $this->isDisplayTabular();
        $url = $this->generateUrl($tabular ? 'calculation_table' : 'calculation_card');

        $data = $this->getChartMonthData($count, $repository, $service, $url);
        $data['tabular'] = $tabular;
        $data['allowed_months'] = $this->getAllowedMonths($repository);

        return $this->renderForm('chart/by_month_chart.html.twig', $data);
    }

    /**
     * Gets the calculations by state.
     *
     * @Route("/state", name="chart_by_state")
     */
    public function byState(CalculationStateRepository $repository, ThemeService $service): Response
    {
        $tabular = $this->isDisplayTabular();
        $data = $this->getChartStateData($repository, $service, $tabular);
        $data['tabular'] = $tabular;

        return $this->renderForm('chart/by_state_chart.html.twig', $data);
    }

    /**
     * Creates and initialize a chart.
     *
     * @param bool $init_options true to initialize language options
     */
    private function createChart($init_options = true): Basechart
    {
        $chart = new Basechart();
        if ($init_options) {
            $chart->initLangOptions();
        }
        $chart->chart->backgroundColor('transparent');

        return $chart;
    }

    /**
     * Generate the URL to display calculations when a state is clicked in pie chart.
     */
    private function generateStateUrl(array $state, bool $tabular): string
    {
        if ($tabular) {
            $route = 'calculation_table';
            $parameters = [
                'search[0][index]' => 8,
                'search[0][value]' => $state['id'],
            ];
        } else {
            $route = 'calculation_card';
            $parameters = [
                'query' => $state['code'],
            ];
        }

        return $this->generateUrl($route, $parameters);
    }

    /**
     * Gets the allowed months to display.
     */
    private function getAllowedMonths(CalculationRepository $repository): array
    {
        $values = [4, 6, 12, 18, 24];
        $maxMonths = $repository->countDistinctMonths();

        if (\end($values) <= $maxMonths) {
            return $values;
        }

        for ($i = 0, $count = \count($values); $i < $count; ++$i) {
            if ($values[$i] >= $maxMonths) {
                return \array_slice($values, 0, $i + 1);
            }
        }

        // must never been here!
        return $values;
    }

    /**
     * Gets data used by the chart for the calculations by month.
     *
     * @return array the data
     */
    private function getChartMonthData(int $count, CalculationRepository $repository, ThemeService $service, string $url): array
    {
        $months = \max(1, $count);

        // get values
        $data = $repository->getByMonth($months);

        // dates (x values)
        $dates = \array_map(function (array $item): int {
            return $item['date']->getTimestamp() * 1000;
        }, $data);

        // count serie
        $countData = \array_map(function (array $item): int {
            return (int) ($item['count']);
        }, $data);

        // items amount serie
        $itemsData = \array_map(function (array $item): float {
            return (float) ($item['items']);
        }, $data);

        // margin amount serie
        $marginsData = \array_map(function (array $item): float {
            return (float) ($item['total']) - (float) ($item['items']);
        }, $data);

        // total serie
        $sumData = \array_map(function (array $item): float {
            return (float) ($item['total']);
        }, $data);

        // margins (percent)
        $marginsPercent = \array_map(function (array $item): float {
            return (float) $item['margin'];
        }, $data);

        // margins (amount)
        $marginsAmount = \array_map(function (array $item): float {
            return (float) ($item['total']) - (float) ($item['items']);
        }, $data);

        // series
        $series = [
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

        // y axis
        $color = $this->getForeground($service);
        $yAxis = [
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

        // x axis
        $xAxis = [
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

        // tootltip formatter
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
        $formatter = new Expr($function);

        // click event
        $function = <<<EOF
            function() {
                const href = "{$url}?query=" + Highcharts.dateFormat("%m.%Y", this.category);
                location.href = href;
            }
            EOF;
        $click = new Expr($function);

        // chart
        $chart = $this->createChart();
        $chart->setType(Basechart::TYPE_COLUMN)
            ->hideTitle()
            ->hideLegend()
            ->xAxis($xAxis)
            ->yAxis($yAxis)
            ->series($series);

        $chart->plotOptions->series([
            'stacking' => 'normal',
            'cursor' => 'pointer',
            'point' => [
                'events' => [
                    'click' => $click,
                ],
            ],
        ]);

        $chart->tooltip->useHTML(true)
            ->formatter($formatter);

        // data
        $data = [];
        for ($i = 0, $count = \count($dates); $i < $count; ++$i) {
            $data[] = [
                'date' => ($dates[$i] / 1000),
                'count' => $countData[$i],
                'sum' => $sumData[$i],
                'items' => $itemsData[$i],
                'marginAmount' => $marginsAmount[$i],
                'marginPercent' => $marginsPercent[$i],
            ];
        }

        $count = \array_sum($countData);
        $items = \array_sum($itemsData);
        $total = \array_sum($sumData);
        $marginAmount = $total - $items;
        $marginPercent = $this->safeDivide($total, $items);

        return [
            'chart' => $chart,
            'data' => $data,
            'count' => $count,
            'items' => $items,
            'months' => $months,
            'marginPercent' => $marginPercent,
            'marginAmount' => $marginAmount,
            'total' => $total,
        ];
    }

    /**
     * Gets data used by the chart for the calculations by state.
     *
     * @param CalculationStateRepository $repository the repository to get data
     * @param ThemeService               $service    the service to get theme style
     * @param bool                       $tabular    true to display link to table, false to link to card
     *
     * @return array the data
     */
    private function getChartStateData(CalculationStateRepository $repository, ThemeService $service, bool $tabular): array
    {
        // get values
        $states = $repository->getListCountCalculations();

        // update
        $count = \array_reduce($states, function (float $carry, array $state) {
            return $carry + $state['count'];
        }, 0);
        $total = \array_reduce($states, function (float $carry, array $state) {
            return $carry + $state['total'];
        }, 0);
        $items = \array_reduce($states, function (float $carry, array $state) {
            return $carry + $state['items'];
        }, 0);
        foreach ($states as &$state) {
            $state['percent'] = $this->safeDivide((float) $state['count'], $count);
        }

        // title
        $title = $this->trans('title_by_state', [], 'chart');

        // data
        $data = \array_map(function (array $state) use ($tabular): array {
            return [
                'name' => $state['code'],
                'y' => (float) ($state['total']),
                'url' => $this->generateStateUrl($state, $tabular),
            ];
        }, $states);

        // colors
        $colors = \array_map(function (array $state): string {
            return $state['color'];
        }, $states);

        // series
        $series = [
            [
                'name' => $title,
                'data' => $data,
                'type' => Basechart::TYPE_PIE,
            ],
        ];

        // legend styles
        $style = [
            'fontSize' => '14px',
            'fontWeight' => 'normal',
            'color' => $this->getForeground($service),
        ];

        // click event
        $function = <<<EOF
            function() {
                location.href = this.url;
            }
            EOF;
        $click = new Expr($function);

        // pie options
        $pie = [
            'cursor' => 'pointer',
            'showInLegend' => true,
            'allowPointSelect' => true,
            'dataLabels' => [
                'enabled' => false,
            ],
            'point' => [
                'events' => [
                    'click' => $click,
                ],
            ],
        ];

        // create chart
        $chart = $this->createChart();
        $chart->hideTitle()
            ->series($series);

        $chart->colors = $colors;
        $chart->plotOptions->pie($pie);
        $chart->legend->itemStyle($style)->itemHoverStyle($style);

        // tooltip
        $chart->tooltip->headerFormat('');
        $chart->tooltip->pointFormat('<span><b>{point.name} : {point.y:,.2f}</b> ({point.percentage:.1f}%)</span>');

        return [
            'chart' => $chart,
            'data' => $states,
            'count' => $count,
            'total' => $total,
            'margin' => $this->safeDivide($total, $items),
        ];
    }

    /**
     * Gets the chart's label foreground.
     *
     * @param ThemeService $service the service to get theme style
     *
     * @return string the foreground
     */
    private function getForeground(ThemeService $service): string
    {
        return $service->isDarkTheme() ? 'white' : 'black';
    }
}
