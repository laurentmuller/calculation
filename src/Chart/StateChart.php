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
use Laminas\Json\Expr;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Chart to display calculations by state.
 *
 * @psalm-import-type QueryCalculationType from CalculationStateRepository
 */
class StateChart extends AbstractHighchart
{
    /**
     * Constructor.
     */
    public function __construct(
        ApplicationService $application,
        private readonly CalculationStateRepository $repository,
        private readonly UrlGeneratorInterface $generator
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
        /** @psalm-var array{count: int, total: float} $state */
        foreach ($states as &$state) {
            $state['percentCalculation'] = $this->safeDivide($state['count'], $count);
            $state['percentAmount'] = $this->safeDivide($state['total'], $total);
        }
        $data = \array_map(function (array $state): array {
            $url = $this->generator->generate('calculation_table', [CalculationTable::PARAM_STATE => $state['id']]);

            return [
                'name' => $state['code'],
                'y' => $state['total'],
                'url' => $url,
            ];
        }, $states);

        $this->setType(self::TYPE_PIE)
            ->hideTitle()
            ->setPlotOptions()
            ->setLegendOptions()
            ->setTooltipOptions()
            ->setSeries($this->getSeries($data));
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
            'headerFormat' => '',
            'pointFormat' => '<span><b>{point.name} : {point.y:,.0f}</b> ({point.percentage:.1f}%)</span>',
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
     * @psalm-param QueryCalculationType[] $states
     *
     * @return string[]
     */
    private function getColors(array $states): array
    {
        return \array_map(static fn (array $state): string => $state['color'], $states);
    }

    /**
     * Gets the pie options.
     */
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

    /**
     * Sets the legend options.
     */
    private function setLegendOptions(): self
    {
        $style = $this->getFontStyle();
        $this->legend['itemStyle'] = $style;
        $this->legend['itemHoverStyle'] = $style;

        return $this;
    }

    /**
     * Sets the plot options.
     */
    private function setPlotOptions(): self
    {
        $this->plotOptions['pie'] = $this->getPieOptions();

        return $this;
    }
}
