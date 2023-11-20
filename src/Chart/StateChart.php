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

use App\Repository\CalculationStateRepository;
use App\Service\ApplicationService;
use App\Table\CalculationTable;
use App\Utils\FormatUtils;
use Laminas\Json\Expr;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Chart to display calculations by state.
 *
 * @psalm-import-type QueryCalculationType from CalculationStateRepository
 */
class StateChart extends AbstractHighchart
{
    private const TEMPLATE_NAME = 'chart/chart_state_tooltip.js.twig';

    public function __construct(
        ApplicationService $application,
        private readonly CalculationStateRepository $repository,
        private readonly UrlGeneratorInterface $generator,
        private readonly Environment $twig
    ) {
        parent::__construct($application);
    }

    /**
     * Generate the chart data.
     */
    public function generate(): array
    {
        $states = $this->repository->getCalculations();
        $count = \array_sum(\array_column($states, 'count'));
        $total = \array_sum(\array_column($states, 'total'));
        $items = \array_sum(\array_column($states, 'items'));
        $data = $this->mapData($states);
        $series = $this->getSeries($data);

        $this->setType(self::TYPE_PIE)
            ->hideTitle()
            ->setPlotOptions()
            ->setLegendOptions()
            ->setTooltipOptions()
            ->setSeries($series);
        $this->colors = $this->getColors($states);

        return [
            'chart' => $this,
            'data' => $states,
            'count' => $count,
            'items' => $items,
            'total' => $total,
            'margin' => $this->safeDivide($total, $items),
            'marginAmount' => $total - $items,
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

    private function getClickExpression(): Expr
    {
        $function = <<<JAVA_SCRIPT
            function() {
                location.href = this.url;
            }
            JAVA_SCRIPT;

        return $this->createExpression($function);
    }

    /**
     * @param QueryCalculationType[] $states
     *
     * @return string[]
     */
    private function getColors(array $states): array
    {
        return \array_map(static fn (array $state): string => $state['color'], $states);
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
                'events' => [
                    'click' => $this->getClickExpression(),
                ],
            ],
        ];
    }

    private function getSeries(array $data): array
    {
        return [
            [
                'data' => $data,
                'name' => $this->transChart('title_by_state'),
            ],
        ];
    }

    private function getURL(int $id): string
    {
        return $this->generator->generate('calculation_table', [CalculationTable::PARAM_STATE => $id]);
    }

    /**
     * @psalm-param QueryCalculationType[] $states
     */
    private function mapData(array $states): array
    {
        return \array_map(function (array $state): array {
            return [
                'name' => $state['code'],
                'y' => $state['total'],
                'calculations' => FormatUtils::formatInt($state['count']),
                'calculations_percent' => FormatUtils::formatPercent($state['percentCalculation'], true, 2, \NumberFormatter::ROUND_HALFEVEN),
                'net_amount' => FormatUtils::formatInt($state['items']),
                'margin_amount' => FormatUtils::formatInt($state['marginAmount']),
                'margin_percent' => FormatUtils::formatPercent($state['margin']),
                'total_amount' => FormatUtils::formatInt($state['total']),
                'total_percent' => FormatUtils::formatPercent($state['percentAmount'], true, 2, \NumberFormatter::ROUND_HALFEVEN),
                'url' => $this->getURL($state['id']),
            ];
        }, $states);
    }

    private function setLegendOptions(): self
    {
        $style = $this->getFontStyle();
        $this->legend->merge([
            'align' => 'left',
            'layout' => 'vertical',
            'verticalAlign' => 'top',
            'itemStyle' => $style,
            'itemHoverStyle' => $style,
        ]);

        return $this;
    }

    private function setPlotOptions(): self
    {
        $this->plotOptions->merge([
            'pie' => $this->getPieOptions(),
            'series' => [
                'keys' => [
                    'name',
                    'y',
                    'calculations',
                    'calculations_percent',
                    'net_amount',
                    'margin_amount',
                    'margin_percent',
                    'total_amount',
                    'total_percent',
                ],
            ],
        ]);

        return $this;
    }
}
