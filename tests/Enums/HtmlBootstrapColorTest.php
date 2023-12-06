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

namespace App\Tests\Enums;

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Html\HtmlBootstrapColor;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(HtmlBootstrapColor::class)]
class HtmlBootstrapColorTest extends TestCase
{
    public static function getColorValues(): array
    {
        return [
            [HtmlBootstrapColor::DANGER, '#DC3545'],
            [HtmlBootstrapColor::DARK, '#343A40'],
            [HtmlBootstrapColor::INFO, '#17A2B8'],
            [HtmlBootstrapColor::LIGHT, '#F8F9FA'],
            [HtmlBootstrapColor::PRIMARY, '#007BFF'],
            [HtmlBootstrapColor::SECONDARY, '#6C757D'],
            [HtmlBootstrapColor::SUCCESS, '#28A745'],
            [HtmlBootstrapColor::WARNING, '#FFC107'],
        ];
    }

    public static function getParseBorderColors(): \Generator
    {
        yield ['border-primary', HtmlBootstrapColor::PRIMARY];
        yield ['border-secondary', HtmlBootstrapColor::SECONDARY];
        yield ['border-success', HtmlBootstrapColor::SUCCESS];
        yield ['border-danger', HtmlBootstrapColor::DANGER];
        yield ['border-warning', HtmlBootstrapColor::WARNING];
        yield ['border-info', HtmlBootstrapColor::INFO];
        yield ['border-light', HtmlBootstrapColor::LIGHT];
        yield ['border-dark', HtmlBootstrapColor::DARK];

        yield ['', null];
        yield ['empty-class', null];
    }

    public static function getParseFillColors(): \Generator
    {
        yield ['bg-primary', HtmlBootstrapColor::PRIMARY];
        yield ['bg-secondary', HtmlBootstrapColor::SECONDARY];
        yield ['bg-success', HtmlBootstrapColor::SUCCESS];
        yield ['bg-danger', HtmlBootstrapColor::DANGER];
        yield ['bg-warning', HtmlBootstrapColor::WARNING];
        yield ['bg-info', HtmlBootstrapColor::INFO];
        yield ['bg-light', HtmlBootstrapColor::LIGHT];
        yield ['bg-dark', HtmlBootstrapColor::DARK];

        yield ['text-bg-primary', HtmlBootstrapColor::PRIMARY];
        yield ['text-bg-secondary', HtmlBootstrapColor::SECONDARY];
        yield ['text-bg-success', HtmlBootstrapColor::SUCCESS];
        yield ['text-bg-danger', HtmlBootstrapColor::DANGER];
        yield ['text-bg-warning', HtmlBootstrapColor::WARNING];
        yield ['text-bg-info', HtmlBootstrapColor::INFO];
        yield ['text-bg-light', HtmlBootstrapColor::LIGHT];
        yield ['text-bg-dark', HtmlBootstrapColor::DARK];

        yield ['', null];
        yield ['empty-class', null];
    }

    public static function getParseTextColors(): \Generator
    {
        yield ['text-primary', HtmlBootstrapColor::PRIMARY];
        yield ['text-secondary', HtmlBootstrapColor::SECONDARY];
        yield ['text-success', HtmlBootstrapColor::SUCCESS];
        yield ['text-danger', HtmlBootstrapColor::DANGER];
        yield ['text-warning', HtmlBootstrapColor::WARNING];
        yield ['text-info', HtmlBootstrapColor::INFO];
        yield ['text-light', HtmlBootstrapColor::LIGHT];
        yield ['text-dark', HtmlBootstrapColor::DARK];

        yield ['', null];
        yield ['empty-class', null];
    }

    public static function getPhpOfficeColors(): array
    {
        return [
            [HtmlBootstrapColor::DANGER, 'DC3545'],
            [HtmlBootstrapColor::DARK, '343A40'],
            [HtmlBootstrapColor::INFO, '17A2B8'],
            [HtmlBootstrapColor::LIGHT, 'F8F9FA'],
            [HtmlBootstrapColor::PRIMARY, '007BFF'],
            [HtmlBootstrapColor::SECONDARY, '6C757D'],
            [HtmlBootstrapColor::SUCCESS, '28A745'],
            [HtmlBootstrapColor::WARNING, 'FFC107'],
        ];
    }

    public function testCount(): void
    {
        self::assertCount(8, HtmlBootstrapColor::cases());
    }

    public function testDrawColor(): void
    {
        $this->handleColors(static fn (HtmlBootstrapColor $color): PdfDrawColor => $color->getDrawColor());
    }

    public function testFillColor(): void
    {
        $this->handleColors(static fn (HtmlBootstrapColor $color): PdfFillColor => $color->getFillColor());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getParseBorderColors')]
    public function testParseBorderColor(string $class, ?HtmlBootstrapColor $color): void
    {
        $actual = HtmlBootstrapColor::parseBorderColor($class);
        if ($color instanceof HtmlBootstrapColor) {
            $expected = $color->getDrawColor();
            self::assertEqualsCanonicalizing($expected, $actual);
        } else {
            self::assertNull($actual);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getParseFillColors')]
    public function testParseFillColor(string $class, ?HtmlBootstrapColor $color): void
    {
        $actual = HtmlBootstrapColor::parseFillColor($class);
        if ($color instanceof HtmlBootstrapColor) {
            $expected = $color->getFillColor();
            self::assertEqualsCanonicalizing($expected, $actual);
        } else {
            self::assertNull($actual);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getParseTextColors')]
    public function testParseTextColor(string $class, ?HtmlBootstrapColor $color): void
    {
        $actual = HtmlBootstrapColor::parseTextColor($class);
        if ($color instanceof HtmlBootstrapColor) {
            $expected = $color->getTextColor();
            self::assertEqualsCanonicalizing($expected, $actual);
        } else {
            self::assertNull($actual);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getPhpOfficeColors')]
    public function testPhpOfficeColor(HtmlBootstrapColor $color, string $expected): void
    {
        $actual = $color->getPhpOfficeColor();
        self::assertSame($expected, $actual);
    }

    public function testTextColor(): void
    {
        $this->handleColors(static fn (HtmlBootstrapColor $color): PdfTextColor => $color->getTextColor());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getColorValues')]
    public function testValue(HtmlBootstrapColor $color, string $expected): void
    {
        $actual = $color->value;
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param callable(HtmlBootstrapColor): ?object $fn
     */
    private function handleColors(callable $fn): void
    {
        $colors = HtmlBootstrapColor::cases();
        foreach ($colors as $color) {
            $actual = $fn($color);
            self::assertNotNull($actual);
        }
    }
}
