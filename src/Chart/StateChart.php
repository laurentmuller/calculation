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

use App\Model\CalculationsStateItem;
use App\Model\CalculationsTotal;
use App\Parameter\ApplicationParameters;
use App\Repository\CalculationStateRepository;
use App\Table\CalculationTable;
use App\Utils\FormatUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Chart to display calculations by state.
 */
class StateChart extends AbstractHighchart
{
    private const TEMPLATE_NAME = 'chart/_state_tooltip.js.twig';

    public function __construct(
        ApplicationParameters $parameters,
        UrlGeneratorInterface $generator,
        Environment $twig,
        private readonly CalculationStateRepository $repository,
    ) {
        parent::__construct($parameters, $generator, $twig);
    }

    /**
     * Generate the chart data.
     *
     * @return array{
     *     chart: StateChart,
     *     data: CalculationsStateItem[],
     *     total: CalculationsTotal,
     *     minMargin: float}
     */
    public function generate(): array
    {
        $calculationsState = $this->repository->getCalculations();
        $items = $calculationsState->items;
        $total = $calculationsState->total;

        $this->setType(self::TYPE_PIE)
            ->hideTitle()
            ->setPlotOptions()
            ->setTooltipOptions()
            ->setColors($items)
            ->setSeries($items);

        return [
            'chart' => $this,
            'data' => $items,
            'total' => $total,
            'minMargin' => $this->getMinMargin(),
        ];
    }

    #[\Override]
    protected function setTooltipOptions(): static
    {
        parent::setTooltipOptions();
        $this->tooltip->merge([
            'formatter' => $this->createTemplateExpression(self::TEMPLATE_NAME),
            'useHTML' => true,
            'shared' => true,
        ]);

        return $this;
    }

    private function formatPercent(float $value): string
    {
        return FormatUtils::formatPercent($value, true, 2, \NumberFormatter::ROUND_HALFEVEN);
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
                'y',
                'id',
                'name',
                'count',
                'calculationsPercent',
                'items',
                'marginAmount',
                'marginPercent',
                'marginColor',
                'totalPercent',
                'totalAmount',
                'url',
            ],
        ];
    }

    private function getURL(int $id): string
    {
        return $this->generator->generate('calculation_index', [CalculationTable::PARAM_STATE => $id]);
    }

    /**
     * @param CalculationsStateItem[] $items
     */
    private function mapData(array $items): array
    {
        return \array_map(fn (CalculationsStateItem $entry): array => [
            'y' => $entry->total,
            'id' => $entry->id,
            'name' => $entry->code,
            'count' => FormatUtils::formatInt($entry->count),
            'calculationsPercent' => $this->formatPercent($entry->calculationsPercent),
            'items' => FormatUtils::formatInt($entry->items),
            'marginPercent' => FormatUtils::formatPercent($entry->marginPercent),
            'marginAmount' => FormatUtils::formatInt($entry->marginAmount),
            'marginClass' => $this->getMarginClass($entry->marginPercent),
            'totalAmount' => FormatUtils::formatInt($entry->total),
            'totalPercent' => $this->formatPercent($entry->totalPercent),
            'url' => $this->getURL($entry->id),
        ], $items);
    }

    /**
     * @param CalculationsStateItem[] $items
     */
    private function setColors(array $items): self
    {
        $this->colors = \array_map(static fn (CalculationsStateItem $state): string => $state->color, $items);

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
     * @phpstan-param CalculationsStateItem[] $items
     */
    private function setSeries(array $items): void
    {
        $this->series->merge([
            [
                'name' => $this->trans('chart.state.title'),
                'data' => $this->mapData($items),
            ],
        ]);
    }
}
