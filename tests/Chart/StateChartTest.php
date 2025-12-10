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

namespace App\Tests\Chart;

use App\Chart\StateChart;
use App\Model\CalculationsState;
use App\Model\CalculationsStateItem;
use App\Parameter\ApplicationParameters;
use App\Repository\CalculationStateRepository;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class StateChartTest extends TestCase
{
    use TranslatorMockTrait;

    private MockObject&UrlGeneratorInterface $generator;
    private MockObject&ApplicationParameters $parameters;
    private MockObject&CalculationStateRepository $repository;
    private MockObject&TranslatorInterface $translator;
    private MockObject&Environment $twig;

    #[\Override]
    protected function setUp(): void
    {
        $this->parameters = $this->createMock(ApplicationParameters::class);
        $this->repository = $this->createMock(CalculationStateRepository::class);
        $this->generator = $this->createMock(UrlGeneratorInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->translator = $this->createMockTranslator();
    }

    public function testWithoutData(): void
    {
        $this->repository->method('getCalculations')
            ->willReturn(new CalculationsState([]));
        $chart = $this->createChart();
        $chart->generate();
        self::expectNotToPerformAssertions();
    }

    public function testWithSeries(): void
    {
        $this->repository->method('getCalculations')
            ->willReturn($this->createCalculationsState());

        $chart = $this->createChart();
        $chart->generate();
        self::expectNotToPerformAssertions();
    }

    private function createCalculationsState(): CalculationsState
    {
        $items = [
            new CalculationsStateItem(
                id: 1,
                code: 'code1',
                editable: true,
                color: 'red',
                count: 1,
                items: 100.0,
                total: 200,
            ),
            new CalculationsStateItem(
                id: 2,
                code: 'code2',
                editable: false,
                color: 'pink',
                count: 1,
                items: 100.0,
                total: 200,
            ),
        ];

        return new CalculationsState($items);
    }

    private function createChart(): StateChart
    {
        $chart = new StateChart($this->parameters, $this->generator, $this->twig, $this->repository);
        $chart->setTranslator($this->translator);

        return $chart;
    }
}
