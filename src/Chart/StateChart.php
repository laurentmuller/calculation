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
        $series = $this->mapData($states);

        $this->setType(self::TYPE_PIE)
            ->hideTitle()
            ->setPlotOptions()
            ->setTooltipOptions()
            ->setColors($states)
            ->setSeries($series);

        return [
            'chart' => $this,
            'data' => $states,
            'min_margin' => $this->getMinMargin(),
            'totals' => $this->getTotals($states),
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

    /**
     * @param QueryCalculationType[] $states
     */
    private function getTotals(array $states): array
    {
        $count = \array_sum(\array_column($states, 'count'));
        $total = \array_sum(\array_column($states, 'total'));
        $items = \array_sum(\array_column($states, 'items'));

        return [
            'count' => $count,
            'items' => $items,
            'total' => $total,
            'margin' => $this->safeDivide($total, $items),
            'margin_amount' => $total - $items,
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
                'calculations_percent' => FormatUtils::formatPercent($state['percent_calculation'], true, 2, \NumberFormatter::ROUND_HALFEVEN),
                'net_amount' => FormatUtils::formatInt($state['items']),
                'margin_amount' => FormatUtils::formatInt($state['margin_amount']),
                'margin_percent' => FormatUtils::formatPercent($state['margin']),
                'total_amount' => FormatUtils::formatInt($state['total']),
                'total_percent' => FormatUtils::formatPercent($state['percent_amount'], true, 2, \NumberFormatter::ROUND_HALFEVEN),
                'url' => $this->getURL($state['id']),
            ];
        }, $states);
    }

    /**
     * @param QueryCalculationType[] $states
     */
    private function setColors(array $states): self
    {
        $this->colors = \array_map(static fn (array $state): string => $state['color'], $states);

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

    private function setSeries(array $data): void
    {
        $this->series->merge([
            [
                'data' => $data,
                'name' => $this->transChart('title_by_state'),
            ],
        ]);
    }
}
