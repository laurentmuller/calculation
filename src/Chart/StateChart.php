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
use App\Service\ThemeService;
use App\Table\CalculationTable;
use Laminas\Json\Expr;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Chart to display calculations by state.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class StateChart extends BaseChart
{
    /**
     * Constructor.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function __construct(ApplicationService $application, ThemeService $service, private readonly CalculationStateRepository $repository, private readonly UrlGeneratorInterface $generator)
    {
        parent::__construct($application, $service);
    }

    /**
     * Generate the chart data.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function generate(): array
    {
        $states = $this->repository->getListCountCalculations();
        $count = \array_sum(\array_column($states, 'count'));
        $total = \array_sum(\array_column($states, 'total'));
        $items = \array_sum(\array_column($states, 'items'));

        /** @psalm-var array $state */
        foreach ($states as &$state) {
            $state['percentCalculation'] = $this->safeDivide((float) $state['count'], $count);
            $state['percentAmount'] = $this->safeDivide((float) $state['total'], $total);
        }

        $data = \array_map(function (array $state): array {
            $url = $this->generator->generate('calculation_table', [CalculationTable::PARAM_STATE => $state['id']]);

            return [
                'name' => $state['code'],
                'y' => $state['total'],
                'url' => $url,
            ];
        }, $states);

        $style = $this->getStyle();

        $this->hideTitle()
            ->series($this->getSeries($data));

        $this->colors = $this->getColors($states);
        $this->plotOptions->pie($this->getPie()); // @phpstan-ignore-line
        $this->legend->itemStyle($style)->itemHoverStyle($style); // @phpstan-ignore-line

        $this->tooltip->headerFormat(''); // @phpstan-ignore-line
        $this->tooltip->pointFormat('<span><b>{point.name} : {point.y:,.0f}</b> ({point.percentage:.1f}%)</span>'); // @phpstan-ignore-line

        return [
                'chart' => $this,
                'data' => $states,
                'count' => $count,
                'items' => $items,
                'total' => $total,
                'margin' => $this->safeDivide($total, $items),
                'marginAmount' => $total - $items,
                'min_margin' => $this->application->getMinMargin(),
            ];
    }

    private function getClickExpression(): Expr
    {
        $function = <<<EOF
            function() {
                location.href = this.url;
            }
            EOF;

        return new Expr($function);
    }

    /**
     * @param array<array{
     *      id: int,
     *      code: string,
     *      editable: boolean,
     *      color: string,
     *      count: int,
     *      items: float,
     *      total: float,
     *      margin: float,
     *      marginAmount: float}> $states
     *
     * @return string[]
     */
    private function getColors(array $states): array
    {
        return \array_map(fn (array $state): string => $state['color'], $states);
    }

    private function getPie(): array
    {
        $click = $this->getClickExpression();

        return [
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
    }

    private function getSeries(array $data): array
    {
        return [
            [
                'data' => $data,
                'name' => $this->trans('title_by_state', [], 'chart'),
                'type' => self::TYPE_PIE,
            ],
        ];
    }

    private function getStyle(): array
    {
        return [
            'fontSize' => '14px',
            'fontWeight' => 'normal',
            'color' => $this->getForeground(),
        ];
    }
}
