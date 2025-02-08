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

use App\Chart\MonthChart;
use App\Repository\CalculationRepository;
use App\Service\ApplicationService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @psalm-import-type CalculationByMonthType from CalculationRepository
 */
class MonthChartTest extends TestCase
{
    use TranslatorMockTrait;

    private MockObject&ApplicationService $application;
    private MockObject&UrlGeneratorInterface $generator;
    private MockObject&CalculationRepository $repository;
    private MockObject&TranslatorInterface $translator;
    private MockObject&Environment $twig;

    protected function setUp(): void
    {
        $this->application = $this->createMock(ApplicationService::class);
        $this->repository = $this->createMock(CalculationRepository::class);
        $this->generator = $this->createMock(UrlGeneratorInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->translator = $this->createMockTranslator();
    }

    public function testWithElevenMonths(): void
    {
        $this->repository->method('countDistinctMonths')
            ->willReturn(11);

        $chart = $this->createChart();
        $chart->generate(1);
        self::assertInstanceOf(MonthChart::class, $chart);
    }

    public function testWithOneMonth(): void
    {
        $this->repository->method('countDistinctMonths')
            ->willReturn(1);

        $chart = $this->createChart();
        $chart->generate(1);
        self::assertInstanceOf(MonthChart::class, $chart);
    }

    public function testWithSeries(): void
    {
        $this->repository->method('countDistinctMonths')
            ->willReturn(6);
        $this->repository->method('getByMonth')
            ->willReturn($this->createSeries());

        $chart = $this->createChart();
        $chart->generate(6);
        self::assertInstanceOf(MonthChart::class, $chart);
    }

    private function createChart(): MonthChart
    {
        $chart = new MonthChart($this->application, $this->generator, $this->twig, $this->repository);
        $chart->setTranslator($this->translator);

        return $chart;
    }

    /**
     * @psalm-return CalculationByMonthType[]
     */
    private function createSeries(): array
    {
        $value0 = [
            'count' => 1,
            'items' => 100.0,
            'total' => 200,
            'year' => 2024,
            'month' => 6,
            'margin_percent' => 0.25,
            'margin_amount' => 50.0,
            'date' => new \DateTime('2024-6-05'),
        ];

        $value1 = [
            'count' => 1,
            'items' => 100.0,
            'total' => 200,
            'year' => 2024,
            'month' => 6,
            'margin_percent' => -0.25,
            'margin_amount' => 50.0,
            'date' => new \DateTime('2024-6-05'),
        ];

        return [$value0, $value1];
    }
}
