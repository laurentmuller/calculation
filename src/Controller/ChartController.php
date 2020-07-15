<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Chart\Basechart;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Service\ThemeService;
use App\Traits\MathTrait;
use App\Utils\DateUtils;
use Laminas\Json\Expr;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for charts.
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
     * @Route("/month/{count}", name="chart_by_month", requirements={"count": "\d+" })
     */
    public function byMonth(int $count = 12, CalculationRepository $repository, ThemeService $service): Response
    {
        $tabular = $this->getApplication()->isDisplayTabular();
        $url = $this->generateUrl($tabular ? 'calculation_table' : 'calculation_list');

        $data = $this->getChartMonthData($count, $repository, $service, $url);
        $data['tabular'] = \json_encode($this->getApplication()->isDisplayTabular());

        return $this->render('chart/last_month_chart.html.twig', $data);
    }

    /**
     * Gets the calculations by state.
     *
     * @Route("/state", name="chart_by_state")
     */
    public function byState(CalculationStateRepository $repository, ThemeService $service): Response
    {
        $tabular = $this->getApplication()->isDisplayTabular();
        $url = $this->generateUrl($tabular ? 'calculation_table' : 'calculation_list');

        $data = $this->getChartStateData($repository, $service, $url);
        $data['tabular'] = $this->getApplication()->isDisplayTabular();

        return $this->render('chart/by_state_chart.html.twig', $data);
    }

    /**
     * @Route("/default", name="chart_default")
     */
    public function default(): Response
    {
        return $this->render('chart/base_chart.html.twig', [
            'title' => 'Graphique par défaut',
            'chart' => $this->defaultChart(),
        ]);
    }

    /**
     * @Route("/drilldown", name="chart_drilldown")
     */
    public function drillDown(): Response
    {
        return $this->render('chart/base_chart.html.twig', [
            'title' => 'Drill-Down',
            'chart' => $this->drillDownChart(),
        ]);
    }

    /**
     * Gets data used by the chart for the calculations by month.
     *
     * @return array the data
     */
    public function getChartMonthData(int $count, CalculationRepository $repository, ThemeService $service, string $url): array
    {
        $months = \max(1, $count);

        // get values
        $data = $repository->getByMonth($months);

        // dates (x values)
        $dates = \array_map(function (array $item) {
            return $item['date']->getTimestamp() * 1000;
        }, $data);

        // count serie
        $countData = \array_map(function (array $item) {
            return (int) ($item['count']);
        }, $data);

        // items amount serie
        $itemsData = \array_map(function (array $item) {
            return (float) ($item['items']);
        }, $data);

        // margin amount serie
        $marginsData = \array_map(function (array $item) {
            return (float) ($item['total']) - (float) ($item['items']);
        }, $data);

        // total serie
        $sumData = \array_map(function (array $item) {
            return (float) ($item['total']);
        }, $data);

        // margins (percent)
        $marginsPercent = \array_map(function (array $item) {
            $items = (float) ($item['items']);
            $total = (float) ($item['total']);
            if ($this->isFloatZero($total)) {
                return 0;
            }
            $margin = $this->safeDivide($total, $items);
            if (!$this->isFloatZero($margin)) {
                --$margin;
            }

            return $margin;
        }, $data);

        // margins (amount)
        $marginsAmount = \array_map(function (array $item) {
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
        $chart = $this->createChart(true);
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
        if (!$this->isFloatZero($marginPercent)) {
            $marginPercent = -1;
        }

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
     *
     * @return array the data
     */
    public function getChartStateData(CalculationStateRepository $repository, ThemeService $service, string $url): array
    {
        // get values
        $states = $repository->getByState();

        // update percents
        $count = \array_reduce($states, function (float $carry, array $state) {
            return $carry + $state['count'];
        }, 0);
        $total = \array_reduce($states, function (float $carry, array $state) {
            return $carry + $state['total'];
        }, 0);
        foreach ($states as &$state) {
            $state['percent'] = $this->safeDivide((float) $state['total'], $total);
        }

        // title
        $title = $this->trans('title_by_state', [], 'chart');

        // data
        $data = \array_map(function (array $state) {
            return [
                'name' => $state['code'],
                'y' => (float) ($state['total']),
            ];
        }, $states);

        // colors
        $colors = \array_map(function (array $state) {
            return $state['color'];
        }, $states);

        // series
        $series = [
            [
                'type' => Basechart::TYPE_PIE,
                'data' => $data,
                'name' => $title,
            ],
        ];

        // legend styles
        $color = $this->getForeground($service);
        $style = [
            'color' => $color,
            'fontWeight' => 'normal',
            'fontSize' => '14px',
        ];

        // click event
        $function = <<<EOF
            function() {
                const href = "{$url}?query=" + this.name;
                location.href = href;
            }
            EOF;
        $click = new Expr($function);

        // pie options
        $pie = [
            'allowPointSelect' => true,
            'cursor' => 'pointer',
            'showInLegend' => true,
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
        ];
    }

    /**
     * @Route("/multiaxes", name="chart_multiaxes")
     */
    public function multiAxes(): Response
    {
        return $this->render('chart/base_chart.html.twig', [
            'title' => 'Multi-Axes',
            'chart' => $this->multiAxesChart(),
        ]);
    }

    /**
     * @Route("/history", name="chart_history")
     */
    public function sellsHistory(): Response
    {
        return $this->render('chart/base_chart.html.twig', [
            'title' => 'Historique',
            'chart' => $this->sellsHistoryChart(),
        ]);
    }

    /**
     * Creates and initialize a chart.
     *
     * @param bool $init_options true to initialize language options
     */
    private function createChart($init_options = true): Basechart
    {
        $chart = new Basechart($this->getApplication());
        if ($init_options) {
            $chart->initLangOptions();
        }
        $chart->chart->backgroundColor('transparent');

        return $chart;
    }

    private function defaultChart(): Basechart
    {
        // Chart
        $series = [
            [
                'name' => 'Data Serie Name',
                'data' => [
                    1,
                    2,
                    4,
                    5,
                    6,
                    3,
                    8,
                ],
            ],
        ];

        $chart = $this->createChart();
        $chart->setTitle('Chart Title');
        $chart->setXAxisTitle('Horizontal axis title');
        $chart->setYAxisTitle('Vertical axis title');
        $chart->series($series);

        return $chart;
    }

    private function drillDownChart(): Basechart
    {
        $chart = $this->createChart(true);
        $chart->setTitle('Browser market shares. November, 2013.');
        $chart->setType(Basechart::TYPE_PIE);

        $chart->plotOptions->series([
            'dataLabels' => [
                'enabled' => true,
                'format' => '{point.name} : {point.y:.1f}%',
            ],
        ]);

        $chart->tooltip->headerFormat('<span style="font-size:14px">{series.name}</span><br>');
        $chart->tooltip->pointFormat('<span style="color:{point.color};">{point.name}</span>: <b>{point.y:.2f}%');

        $data = [
            [
                'name' => 'Chrome',
                'y' => 18.73,
                'drilldown' => 'chrome',
                'visible' => true,
            ],
            [
                'name' => 'Microsoft Internet Explorer',
                'y' => 53.61,
                'drilldown' => 'explorer',
                'visible' => true,
            ],
            [
                'Firefox',
                45.0,
            ],
            [
                'Opera',
                1.5,
            ],
        ];
        $chart->series([
            [
                'name' => 'Browsers',
                'colorByPoint' => true,
                'data' => $data,
            ],
        ]);

        $drillUpButton = [
            'relativeTo' => 'spacingBox',
            'position' => [
                'align' => 'right',
                'y' => 40,
                'x' => 0,
            ],
            'theme' => [
                'fill' => '#007bff',
                'stroke' => '#007bff',
                'stroke-width' => 1,
                'r' => '4',
                'states' => [
                    'hover' => [
                        'fill' => '#0069d9',
                        'stroke' => '#0062cc',
                    ],
                    'select' => [
                        'fill' => '#0069d9',
                        'stroke' => '#0062cc',
                    ],
                ],
            ],
        ];

        $drilldown = [
            [
                'name' => 'Microsoft Internet Explorer',
                'id' => 'explorer',
                'data' => [
                    [
                        'v8.0',
                        26.61,
                    ],
                    [
                        'v9.0',
                        16.96,
                    ],
                    [
                        'v6.0',
                        6.4,
                    ],
                    [
                        'v7.0',
                        3.55,
                    ],
                    [
                        'v8.0',
                        0.09,
                    ],
                ],
            ],
            [
                'name' => 'Chrome',
                'id' => 'chrome',
                'data' => [
                    [
                        'v19.0',
                        7.73,
                    ],
                    [
                        'v17.0',
                        1.13,
                    ],
                    [
                        'v16.0',
                        0.45,
                    ],
                    [
                        'v18.0',
                        0.26,
                    ],
                ],
            ],
        ];
        $chart->drilldown->drillUpButton($drillUpButton);
        $chart->drilldown->series($drilldown);

        return $chart;
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
        return $service->getCurrentTheme()->isDark() ? 'white' : 'black';
    }

    private function multiAxesChart(): Basechart
    {
        $series = [
            [
                'name' => 'Pluviométrie',
                'type' => Basechart::TYPE_COLUMN,
                'color' => '#4572A7',
                'yAxis' => 1,
                'data' => [
                    49.9,
                    71.5,
                    106.4,
                    129.2,
                    144.0,
                    176.0,
                    135.6,
                    148.5,
                    216.4,
                    194.1,
                    95.6,
                    54.4,
                ],
            ],
            [
                'name' => 'Température',
                'type' => Basechart::TYPE_SP_LINE,
                'color' => '#AA4643',
                'data' => [
                    7.0,
                    6.9,
                    9.5,
                    14.5,
                    18.2,
                    21.5,
                    25.2,
                    26.5,
                    23.3,
                    18.3,
                    13.9,
                    9.6,
                ],
            ],
        ];
        $yAxis = [
            [
                'labels' => [
                    'formatter' => new Expr('function () { return this.value + "°C" }'),
                    'style' => [
                        'color' => '#AA4643',
                    ],
                ],
                'title' => [
                    'text' => 'Température',
                    'style' => [
                        'color' => '#AA4643',
                    ],
                ],
                'opposite' => true,
            ],
            [
                'labels' => [
                    'formatter' => new Expr('function () { return this.value + " mm" }'),
                    'style' => [
                        'color' => '#4572A7',
                    ],
                ],
                'gridLineWidth' => 0,
                'title' => [
                    'text' => 'Pluviométrie',
                    'style' => [
                        'color' => '#4572A7',
                    ],
                ],
            ],
        ];

        $categories = \array_values(DateUtils::getMonths());

        $chart = $this->createChart();
        $chart->setTitle('Données météorologiques mensuelles moyennes pour Tokyo');
        $chart->setType(Basechart::TYPE_COLUMN);
        $chart->setXAxisCategories($categories);

        $chart->yAxis($yAxis);
        $chart->legend->enabled(false);
        $formatter = new Expr('function () {
                 var unit = {
                     "Pluviométrie": "mm",
                     "Température": "°C"
                 }[this.series.name];
                 return this.x + ": <b>" + this.y + unit+ "</b>";
             }');
        $chart->tooltip->formatter($formatter);
        $chart->series($series);

        return $chart;
    }

    private function sellsHistoryChart(): Basechart
    {
        $sellsHistory = [
            [
                'name' => 'Total des ventes',
                'data' => [
                    683,
                    756,
                    543,
                    1208,
                    617,
                    990,
                    1001,
                ],
            ],
            [
                'name' => 'Ventes en France',
                'data' => [
                    467,
                    321,
                    56,
                    698,
                    134,
                    344,
                    452,
                ],
            ],
        ];

        $dates = [
            '21/06',
            '22/06',
            '23/06',
            '24/06',
            '25/06',
            '26/06',
            '27/06',
        ];

        $chart = $this->createChart();
        $chart->setTitle('Vente du 21/06/2013 au 27/06/2013');
        $chart->setYAxisTitle("Ventes (milliers d'unité)");
        $chart->setXAxisTitle('Date du jours');
        $chart->setXAxisCategories($dates);
        $chart->series($sellsHistory);

        return $chart;
    }
}
