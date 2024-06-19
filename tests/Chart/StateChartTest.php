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

use App\Chart\AbstractHighchart;
use App\Chart\StateChart;
use App\Repository\CalculationStateRepository;
use App\Service\ApplicationService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @psalm-import-type QueryCalculationType from CalculationStateRepository
 */
#[CoversClass(StateChart::class)]
#[CoversClass(AbstractHighchart::class)]
class StateChartTest extends TestCase
{
    use TranslatorMockTrait;

    private MockObject&ApplicationService $application;
    private MockObject&UrlGeneratorInterface $generator;
    private MockObject&CalculationStateRepository $repository;
    private MockObject&TranslatorInterface $translator;
    private MockObject&Environment $twig;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->application = $this->createMock(ApplicationService::class);
        $this->repository = $this->createMock(CalculationStateRepository::class);
        $this->generator = $this->createMock(UrlGeneratorInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->translator = $this->createMockTranslator();
    }

    public function testWithoutData(): void
    {
        $chart = $this->createChart();
        $chart->generate();
        self::assertInstanceOf(StateChart::class, $chart);
    }

    public function testWithSeries(): void
    {
        $this->repository->method('getCalculations')
            ->willReturn($this->createSeries());

        $chart = $this->createChart();
        $chart->generate();
        self::assertInstanceOf(StateChart::class, $chart);
    }

    private function createChart(): StateChart
    {
        $chart = new StateChart($this->application, $this->repository, $this->generator, $this->twig);
        $chart->setTranslator($this->translator);

        return $chart;
    }

    /**
     * @psalm-return QueryCalculationType[]
     */
    private function createSeries(): array
    {
        $value0 = [
            'id' => 1,
            'code' => 'code1',
            'editable' => true,
            'color' => 'red',
            'count' => 1,
            'items' => 100.0,
            'total' => 200,
            'margin_percent' => 0.25,
            'margin_amount' => 50.0,
            'percent_calculation' => 0.25,
            'percent_amount' => 0.25,
        ];

        $value1 = [
            'id' => 2,
            'code' => 'code2',
            'editable' => false,
            'color' => 'pink',
            'count' => 1,
            'items' => 100.0,
            'total' => 200,
            'margin_percent' => -0.25,
            'margin_amount' => 50.0,
            'percent_calculation' => 0.25,
            'percent_amount' => 0.25,
        ];

        return [$value0, $value1];
    }
}
