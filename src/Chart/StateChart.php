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

use App\Repository\CalculationStateRepository;
use App\Service\ApplicationService;
use App\Service\ThemeService;
use App\Table\CalculationTable;
use App\Traits\MathTrait;
use App\Traits\TranslatorTrait;
use Laminas\Json\Expr;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Chart to display calculations by state.
 *
 * @author Laurent Muller
 */
class StateChart extends BaseChart
{
    use MathTrait;
    use TranslatorTrait;

    private ApplicationService $application;
    private UrlGeneratorInterface $generator;
    private CalculationStateRepository $repository;
    private ThemeService $service;

    /**
     * Constructor.
     */
    public function __construct(ApplicationService $application, CalculationStateRepository $repository, ThemeService $service, TranslatorInterface $translator, UrlGeneratorInterface $generator)
    {
        parent::__construct();
        $this->initLangOptions();
        $this->application = $application;
        $this->repository = $repository;
        $this->service = $service;
        $this->generator = $generator;
        $this->setTranslator($translator);
    }

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
        // @phpstan-ignore-next-line
        $this->plotOptions->pie($this->getPie());
        // @phpstan-ignore-next-line
        $this->legend->itemStyle($style)->itemHoverStyle($style);

        // @phpstan-ignore-next-line
        $this->tooltip->headerFormat('');
        // @phpstan-ignore-next-line
        $this->tooltip->pointFormat('<span><b>{point.name} : {point.y:,.0f}</b> ({point.percentage:.1f}%)</span>');

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
        return \array_map(function (array $state): string {
            return $state['color'];
        }, $states);
    }

    /**
     * Gets the chart's label foreground.
     */
    private function getForeground(): string
    {
        return $this->service->isDarkTheme() ? 'white' : 'black';
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
