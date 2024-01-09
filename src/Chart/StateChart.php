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
use App\Traits\ArrayTrait;
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
    use ArrayTrait;

    private const TEMPLATE_NAME = 'chart/_state_tooltip.js.twig';

    public function __construct(
        ApplicationService $application,
        private readonly CalculationStateRepository $repository,
        private readonly UrlGeneratorInterface $generator,
        private readonly Environment $twig
    ) {
        parent::__construct($application);
        $this->chart['height'] = 550;
    }

    /**
     * Generate the chart data.
     */
    public function generate(): array
    {
        $states = $this->repository->getCalculations();

        $this->setType(self::TYPE_PIE)
            ->hideTitle()
            ->setPlotOptions()
            ->setTooltipOptions()
            ->setColors($states)
            ->setSeries($states);

        return [
            'chart' => $this,
            'data' => $states,
            'totals' => $this->getTotals($states),
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

    private function formatPercent(float $value): string
    {
        return FormatUtils::formatPercent($value, true, 2, \NumberFormatter::ROUND_HALFEVEN);
    }

    private function getClickExpression(): Expr
    {
        return self::createExpression('function() {location.href = this.url;}');
    }

    private function getMarginColor(float $value): string
    {
        $minMargin = $this->getMinMargin();
        if (!$this->isFloatZero($value) && $value < $minMargin) {
            return 'var(--bs-danger)';
        }

        return 'inherit';
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

    private function getSeriesOptions(): array
    {
        return [
            'keys' => [
                'name',
                'y',
                'calculations',
                'calculations_percent',
                'net_amount',
                'margin_percent',
                'margin_amount',
                'margin_color',
                'total_amount',
                'total_percent',
                'url',
            ],
        ];
    }

    /**
     * @param QueryCalculationType[] $states
     */
    private function getTotals(array $states): array
    {
        $count = $this->getColumnSum($states, 'count');
        $total = $this->getColumnSum($states, 'total');
        $items = $this->getColumnSum($states, 'items');
        $margin_percent = $this->round($this->safeDivide($total, $items), 4);
        $margin_amount = $total - $items;

        return [
            'count' => $count,
            'items' => $items,
            'total' => $total,
            'margin_percent' => $margin_percent,
            'margin_amount' => $margin_amount,
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
                'calculations_percent' => $this->formatPercent($state['percent_calculation']),
                'net_amount' => FormatUtils::formatInt($state['items']),
                'margin_percent' => FormatUtils::formatPercent($state['margin_percent']),
                'margin_amount' => FormatUtils::formatInt($state['margin_amount']),
                'margin_color' => $this->getMarginColor($state['margin_percent']),
                'total_amount' => FormatUtils::formatInt($state['total']),
                'total_percent' => $this->formatPercent($state['percent_amount']),
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
            'series' => $this->getSeriesOptions(),
        ]);

        return $this;
    }

    /**
     * @psalm-param QueryCalculationType[] $states
     */
    private function setSeries(array $states): void
    {
        $this->series->merge([
            [
                'data' => $this->mapData($states),
                'name' => $this->transChart('title_by_state'),
            ],
        ]);
    }
}
