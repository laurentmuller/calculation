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
use App\Model\MonthChartData;
use App\Model\MonthChartDataItem;
use App\Parameter\ApplicationParameters;
use App\Repository\CalculationRepository;
use App\Tests\TranslatorMockTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MonthChartTest extends TestCase
{
    use TranslatorMockTrait;

    private UrlGeneratorInterface $generator;
    private ApplicationParameters $parameters;
    private MockObject&CalculationRepository $repository;
    private TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->parameters = self::createStub(ApplicationParameters::class);
        $this->repository = $this->createMock(CalculationRepository::class);
        $this->generator = self::createStub(UrlGeneratorInterface::class);
        $this->translator = $this->createMockTranslator();
    }

    public function testWithElevenMonths(): void
    {
        $this->repository->method('countDistinctMonths')
            ->willReturn(11);
        $this->repository->method('getMonthChartData')
            ->willReturn($this->createEmptyCalculationsMonth());

        $chart = $this->createChart();
        $actual = $chart->generate(1);
        self::assertCount(0, $actual['data']);
    }

    public function testWithOneMonth(): void
    {
        $this->repository->method('countDistinctMonths')
            ->willReturn(1);
        $this->repository->method('getMonthChartData')
            ->willReturn($this->createEmptyCalculationsMonth());

        $chart = $this->createChart();
        $actual = $chart->generate(1);
        self::assertCount(0, $actual['data']);
    }

    public function testWithSeries(): void
    {
        $this->repository->method('countDistinctMonths')
            ->willReturn(6);
        $this->repository->method('getMonthChartData')
            ->willReturn($this->createCalculationsMonth());

        $chart = $this->createChart();
        $actual = $chart->generate(6);
        self::assertCount(2, $actual['data']);
    }

    private function createCalculationsMonth(): MonthChartData
    {
        $items = [
            new MonthChartDataItem(
                count: 1,
                items: 100.0,
                total: 200,
                year: 2024,
                month: 6
            ),
            new MonthChartDataItem(
                count: 1,
                items: 100.0,
                total: 200,
                year: 2024,
                month: 6
            )];

        return new MonthChartData($items);
    }

    private function createChart(): MonthChart
    {
        $entityManager = self::createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')
            ->willReturn($this->repository);

        return new MonthChart(
            parameters: $this->parameters,
            generator: $this->generator,
            manager: $entityManager,
            translator: $this->translator
        );
    }

    private function createEmptyCalculationsMonth(): MonthChartData
    {
        return new MonthChartData([]);
    }
}
