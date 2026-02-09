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
use App\Parameter\ApplicationParameters;
use App\Tests\Fixture\FixtureChart;
use HighchartsBundle\Highcharts\ChartExpression;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\Error;

final class AbstractChartTest extends TestCase
{
    public function testCreateInvalidTemplateExpression(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')
            ->willThrowException(new Error('Test Message'));
        $chart = $this->createChart(twig: $twig);

        $actual = $chart->createTemplateExpression('fake');
        self::assertNull($actual);
    }

    public function testCreateTemplateExpression(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())->method('render')
            ->willReturn('fake');
        $chart = $this->createChart(twig: $twig);

        $expected = new ChartExpression('fake');
        $actual = $chart->createTemplateExpression('fake');
        self::assertInstanceOf(ChartExpression::class, $actual);
        self::assertSame((string) $expected, (string) $actual);
    }

    public function testGetClickExpression(): void
    {
        $chart = $this->createChart(twig: self::createStub(Environment::class));
        $expected = 'function() {location.href = this.url;}';
        $actual = $chart->getClickExpression()->getExpression();
        self::assertSame($expected, $actual);
    }

    public function testGetMarginClass(): void
    {
        $parameters = $this->createMock(ApplicationParameters::class);
        $parameters->method('isMarginBelow')
            ->willReturn(true, false);
        $chart = $this->createChart(parameters: $parameters);

        $expected = 'text-danger';
        $actual = $chart->getMarginClass(0.9);
        self::assertSame($expected, $actual);

        $expected = '';
        $actual = $chart->getMarginClass(0.0);
        self::assertSame($expected, $actual);
    }

    public function testGetMinMargin(): void
    {
        $expected = 1.1;
        $parameters = $this->createMock(ApplicationParameters::class);
        $parameters->method('getMinMargin')
            ->willReturn($expected);
        $chart = $this->createChart(parameters: $parameters);

        $actual = $chart->getMinMargin();
        self::assertSame($expected, $actual);
    }

    public function testHideTitle(): void
    {
        $chart = $this->createChart();

        $chart->hideTitle();
        self::assertNull($chart->title['text']);
    }

    public function testInitializeOptions(): void
    {
        $chart = $this->createChart();
        self::assertSame('var(--bs-body-bg)', $chart->chart['backgroundColor']);
        self::assertSame([
            'fontFamily' => 'var(--bs-body-font-family)',
            'fontWeight' => 'var(--bs-body-font-weight)',
            'fontSize' => 'var(--bs-body-font-size)',
        ], $chart->chart['style']);
        self::assertSame('chartContainer', $chart->chart['renderTo']);

        self::assertSame(['color' => 'var(--bs-link-hover-color)'], $chart->legend['itemHoverStyle']);
        self::assertSame([
            'fontFamily' => 'var(--bs-body-font-family)',
            'fontWeight' => 'var(--bs-body-font-weight)',
            'fontSize' => 'var(--bs-body-font-size)',
            'color' => 'var(--bs-body-color)',
        ], $chart->legend['itemStyle']);

        self::assertFalse($chart->accessibility['enabled']);
        self::assertFalse($chart->credits['enabled']);

        self::assertSame('.', $chart->lang['decimalPoint']);
        self::assertSame("'", $chart->lang['thousandsSep']);
    }

    public function testTooltipOptions(): void
    {
        $chart = $this->createChart();
        $chart->setTooltipOptions();

        self::assertSame('var(--bs-light)', $chart->tooltip['backgroundColor']);
        self::assertSame('var(--bs-border-color)', $chart->tooltip['borderColor']);
        self::assertSame([
            'fontFamily' => 'var(--bs-body-font-family)',
            'fontWeight' => 'var(--bs-body-font-weight)',
            'fontSize' => '0.75rem',
        ], $chart->tooltip['style']);
    }

    public function testType(): void
    {
        $chart = $this->createChart();
        self::assertNull(@$chart->chart['type']);
        $expected = AbstractHighchart::TYPE_COLUMN;
        $chart->setType($expected);
        self::assertSame($expected, $chart->chart['type']);
    }

    private function createChart(?ApplicationParameters $parameters = null, ?Environment $twig = null): FixtureChart
    {
        return new FixtureChart(
            $parameters ?? self::createStub(ApplicationParameters::class),
            self::createStub(UrlGeneratorInterface::class),
            $twig ?? self::createStub(Environment::class)
        );
    }
}
