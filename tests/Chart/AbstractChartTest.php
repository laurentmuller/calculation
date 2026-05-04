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

use App\Chart\ChartType;
use App\Parameter\ApplicationParameters;
use App\Tests\Fixture\FixtureChart;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AbstractChartTest extends TestCase
{
    public function testAccessibilityOptions(): void
    {
        $chart = $this->createChart();
        self::assertFalse($chart->accessibility['enabled']);
    }

    public function testAxisOptions(): void
    {
        $chart = $this->createChart();
        self::assertSame('var(--bs-border-color)', $chart->xAxis['gridLineColor']);
        self::assertSame('var(--bs-border-color)', $chart->yAxis['gridLineColor']);
        self::assertSameStyle($chart->xAxis['labels']['style'], '0.875rem', 'var(--bs-body-color)');
        self::assertSameStyle($chart->yAxis['labels']['style'], '0.875rem', 'var(--bs-body-color)');
    }

    public function testChartOptions(): void
    {
        $chart = $this->createChart();
        self::assertNull($chart->title['text']);
        self::assertSame('chartContainer', $chart->chart['renderTo']);
        self::assertSameStyle($chart->chart['style']);
        self::assertSame('var(--bs-body-bg)', $chart->chart['backgroundColor']);
        self::assertIsArray($chart->chart['events']);
        self::assertCount(1, $chart->chart['events']);
    }

    public function testCreditsOptions(): void
    {
        $chart = $this->createChart();
        self::assertFalse($chart->credits['enabled']);
    }

    public function testGetClickExpression(): void
    {
        $chart = $this->createChart();
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

    public function testGetTooltipExpression(): void
    {
        $chart = $this->createChart();
        $expected = 'function(){return renderTooltip(this.options);}';
        $actual = $chart->getTooltipExpression()->getExpression();
        self::assertSame($expected, $actual);
    }

    public function testLangOptions(): void
    {
        $chart = $this->createChart();
        self::assertSame("'", $chart->lang['thousandsSep']);
        self::assertSame('.', $chart->lang['decimalPoint']);
    }

    public function testLegendOptions(): void
    {
        $chart = $this->createChart();
        self::assertSameStyle($chart->legend['itemStyle'], color: 'var(--bs-body-color)');
        self::assertSame(['color' => 'var(--bs-link-hover-color)'], $chart->legend['itemHoverStyle']);
        self::assertSame(['color' => 'var(--bs-secondary)'], $chart->legend['itemHiddenStyle']);
    }

    public function testTooltipOptions(): void
    {
        $chart = $this->createChart();
        self::assertSameStyle($chart->tooltip['style'], '0.75rem');
        self::assertSame('var(--bs-border-color)', $chart->tooltip['borderColor']);
        self::assertSame('var(--bs-light)', $chart->tooltip['backgroundColor']);
        self::assertSame(0, $chart->tooltip['borderRadius']);
        self::assertSame(100, $chart->tooltip['hideDelay']);
        self::assertTrue($chart->tooltip['useHTML']);
        self::assertTrue($chart->tooltip['shared']);
    }

    public function testType(): void
    {
        $chart = $this->createChart();
        self::assertNull(@$chart->chart['type']);
        $expected = ChartType::TYPE_COLUMN;
        $chart->setType($expected);
        self::assertSame($expected->value, $chart->chart['type']);
    }

    protected static function assertSameStyle(
        array $option,
        string $fontSize = 'var(--bs-body-font-size)',
        ?string $color = null
    ): void {
        self::assertSame('var(--bs-body-font-family)', $option['fontFamily']);
        self::assertSame('var(--bs-body-font-weight)', $option['fontWeight']);
        self::assertSame($fontSize, $option['fontSize']);
        if (null === $color) {
            self::assertArrayNotHasKey('color', $option);
        } else {
            self::assertSame($color, $option['color']);
        }
    }

    private function createChart(?ApplicationParameters $parameters = null): FixtureChart
    {
        return new FixtureChart(
            parameters: $parameters ?? self::createStub(ApplicationParameters::class),
            generator: self::createStub(UrlGeneratorInterface::class),
            manager: self::createStub(EntityManagerInterface::class),
            translator: self::createStub(TranslatorInterface::class)
        );
    }
}
