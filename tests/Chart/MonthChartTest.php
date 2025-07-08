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
use App\Model\CalculationsMonth;
use App\Model\CalculationsMonthItem;
use App\Repository\CalculationRepository;
use App\Service\ApplicationService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class MonthChartTest extends TestCase
{
    use TranslatorMockTrait;

    private MockObject&ApplicationService $application;
    private MockObject&UrlGeneratorInterface $generator;
    private MockObject&CalculationRepository $repository;
    private MockObject&TranslatorInterface $translator;
    private MockObject&Environment $twig;

    #[\Override]
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
        $this->repository->method('getByMonth')
            ->willReturn($this->createEmptyCalculationsMonth());

        $chart = $this->createChart();
        $chart->generate(1);
        self::expectNotToPerformAssertions();
    }

    public function testWithOneMonth(): void
    {
        $this->repository->method('countDistinctMonths')
            ->willReturn(1);
        $this->repository->method('getByMonth')
            ->willReturn($this->createEmptyCalculationsMonth());

        $chart = $this->createChart();
        $chart->generate(1);
        self::expectNotToPerformAssertions();
    }

    public function testWithSeries(): void
    {
        $this->repository->method('countDistinctMonths')
            ->willReturn(6);
        $this->repository->method('getByMonth')
            ->willReturn($this->createCalculationsMonth());

        $chart = $this->createChart();
        $chart->generate(6);
        self::expectNotToPerformAssertions();
    }

    private function createCalculationsMonth(): CalculationsMonth
    {
        $items = [
            new CalculationsMonthItem(
                count: 1,
                items: 100.0,
                total: 200,
                year: 2024,
                month: 6
            ),
            new CalculationsMonthItem(
                count: 1,
                items: 100.0,
                total: 200,
                year: 2024,
                month: 6
            )];

        return new CalculationsMonth($items);
    }

    private function createChart(): MonthChart
    {
        $chart = new MonthChart($this->application, $this->generator, $this->twig, $this->repository);
        $chart->setTranslator($this->translator);

        return $chart;
    }

    private function createEmptyCalculationsMonth(): CalculationsMonth
    {
        return new CalculationsMonth([]);
    }
}
