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
use App\Parameter\DefaultParameter;
use HighchartsBundle\Highcharts\ChartExpression;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\Error;

final class AbstractChartTest extends TestCase
{
    public function testCreateInvalidTemplateExpression(): void
    {
        $parameters = $this->createMock(ApplicationParameters::class);
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())->method('render')
            ->willThrowException(new Error('Test Message'));
        $chart = new class($parameters, $generator, $twig)extends AbstractHighchart {
            public function updateExpression(): ?ChartExpression
            {
                return $this->createTemplateExpression('fake');
            }
        };

        $actual = $chart->updateExpression();
        self::assertNull($actual);
    }

    public function testCreateTemplateExpression(): void
    {
        $parameters = $this->createMock(ApplicationParameters::class);
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())->method('render')
            ->willReturn('fake');
        $chart = new class($parameters, $generator, $twig)extends AbstractHighchart {
            #[\Override]
            public function createTemplateExpression(string $template, array $context = []): ?ChartExpression
            {
                return parent::createTemplateExpression($template, $context);
            }
        };

        $expected = new ChartExpression('fake');
        $actual = $chart->createTemplateExpression('fake');
        self::assertInstanceOf(ChartExpression::class, $actual);
        self::assertSame((string) $expected, (string) $actual);
    }

    public function testGetClickExpression(): void
    {
        $parameters = $this->createMock(ApplicationParameters::class);
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $twig = $this->createMock(Environment::class);
        $chart = new class($parameters, $generator, $twig) extends AbstractHighchart {
            #[\Override]
            public function getClickExpression(): ChartExpression
            {
                return parent::getClickExpression();
            }
        };
        $expected = 'function() {location.href = this.url;}';
        $actual = $chart->getClickExpression()->getExpression();
        self::assertSame($expected, $actual);
    }

    public function testGetMarginClass(): void
    {
        $default = $this->createMock(DefaultParameter::class);
        $default->method('getMinMargin')
            ->willReturn(1.0);
        $parameters = $this->createMock(ApplicationParameters::class);
        $parameters->method('getDefault')
            ->willReturn($default);

        $generator = $this->createMock(UrlGeneratorInterface::class);
        $twig = $this->createMock(Environment::class);
        $chart = new class($parameters, $generator, $twig) extends AbstractHighchart {
            #[\Override]
            public function getMarginClass(float $value): string
            {
                return parent::getMarginClass($value);
            }
        };

        $expected = 'text-danger';
        $actual = $chart->getMarginClass(0.9);
        self::assertSame($expected, $actual);

        $expected = '';
        $actual = $chart->getMarginClass(2.0);
        self::assertSame($expected, $actual);
    }

    public function testGetMinMargin(): void
    {
        $expected = 1.1;
        $default = $this->createMock(DefaultParameter::class);
        $default->method('getMinMargin')
            ->willReturn($expected);
        $parameters = $this->createMock(ApplicationParameters::class);
        $parameters->method('getDefault')
            ->willReturn($default);
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $twig = $this->createMock(Environment::class);
        $chart = new class($parameters, $generator, $twig) extends AbstractHighchart {
            #[\Override]
            public function getMinMargin(): float
            {
                return parent::getMinMargin();
            }
        };

        $actual = $chart->getMinMargin();
        self::assertSame($expected, $actual);
    }

    public function testHideTitle(): void
    {
        $parameters = $this->createMock(ApplicationParameters::class);
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $twig = $this->createMock(Environment::class);
        $chart = new class($parameters, $generator, $twig) extends AbstractHighchart {};

        $chart->hideTitle();
        self::assertNull($chart->title['text']);
    }

    public function testInitializeOptions(): void
    {
        $parameters = $this->createMock(ApplicationParameters::class);
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $twig = $this->createMock(Environment::class);
        $chart = new class($parameters, $generator, $twig) extends AbstractHighchart {};

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
        $parameters = $this->createMock(ApplicationParameters::class);
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $twig = $this->createMock(Environment::class);
        $chart = new class($parameters, $generator, $twig)extends AbstractHighchart {
            public function updateTooltipOptions(): void
            {
                parent::setTooltipOptions();
            }
        };

        $chart->updateTooltipOptions();
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
        $parameters = $this->createMock(ApplicationParameters::class);
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $twig = $this->createMock(Environment::class);
        $chart = new class($parameters, $generator, $twig) extends AbstractHighchart {
        };

        self::assertNull($chart->chart['type']);
        $expected = AbstractHighchart::TYPE_COLUMN;
        $chart->setType($expected);
        self::assertSame($expected, $chart->chart['type']);
    }
}
