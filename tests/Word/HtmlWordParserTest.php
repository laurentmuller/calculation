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

namespace App\Tests\Word;

use App\Word\HtmlWordParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HtmlWordParserTest extends TestCase
{
    /**
     * @phpstan-return \Generator<int, array{string, string}>
     */
    public static function getBorders(): \Generator
    {
        yield ['border', 'border:1px #808080 solid;'];
        yield ['border-top', 'border-top:1px #808080 solid;'];
        yield ['border-bottom', 'border-bottom:1px #808080 solid;'];
        yield ['border-start', 'border-left:1px #808080 solid;'];
        yield ['border-end', 'border-right:1px #808080 solid;'];

        yield ['border-0', 'border:0 #000000 none;'];

        yield ['border-top-0', 'border-top:0 #000000 none;'];
        yield ['border-start-0', 'border-left:0 #000000 none;'];
        yield ['border-end-0', 'border-right:0 #000000 none;'];
        yield ['border-bottom-0', 'border-bottom:0 #000000 none;'];
    }

    /**
     * @phpstan-return \Generator<int, array{string, string}>
     */
    public static function getClassToStyles(): \Generator
    {
        yield ['text-start', 'text-align:left;'];
        yield ['text-center', 'text-align:center;'];
        yield ['text-end', 'text-align:right;'];
        yield ['text-justify', 'text-align:justify;'];

        yield ['fw-bold', 'font-weight:bold;'];
        yield ['fst-italic', 'font-style:italic;'];
        yield ['font-monospace', 'font-family:Courier New;'];

        yield ['page-break', 'page-break-after:always;'];
    }

    /**
     * @phpstan-return \Generator<int, array{string, string}>
     */
    public static function getMargins(): \Generator
    {
        yield ['m-0', '0;'];
        yield ['M-0', '0;'];
        yield ['m-1', 'margin:4px;'];
        yield ['m-2', 'margin:8px;'];
        yield ['m-3', 'margin:16px;'];
        yield ['m-4', 'margin:24px;'];
        yield ['m-5', 'margin:48px;'];

        yield ['mt-1', 'margin-top:4px;'];
        yield ['mb-1', 'margin-bottom:4px;'];
        yield ['ms-1', 'margin-left:4px;'];
        yield ['me-1', 'margin-right:4px;'];
        yield ['mx-1', 'margin-left:4px;margin-right:4px;'];
        yield ['my-1', 'margin-top:4px;margin-bottom:4px;'];
    }

    #[DataProvider('getClassToStyles')]
    public function testClassToStyle(string $class, string $expected): void
    {
        $this->assertParserContainsClass($expected, $class);
    }

    #[DataProvider('getBorders')]
    public function testParseBorder(string $class, string $expected): void
    {
        $this->assertParserContainsClass($expected, $class);
    }

    public function testParseClassEmpty(): void
    {
        $content = '<h4 class="">Header Content</h4>';
        $this->assertParserContainsString('h4', $content);
    }

    public function testParseEmpty(): void
    {
        $parser = new HtmlWordParser();
        $actual = $parser->parse('');
        self::assertSame('', $actual);
    }

    #[DataProvider('getMargins')]
    public function testParseMargins(string $class, string $expected): void
    {
        $this->assertParserContainsClass($expected, $class);
    }

    public function testParseMultiClassEmpty(): void
    {
        $content = '<h4 class="  ">Header Content</h4>';
        $this->assertParserContainsString('h4', $content);
    }

    private function assertParserContainsClass(string $expected, string $class): void
    {
        $content = \sprintf('<h4 class="%s">Header Content</h4>', $class);
        $this->assertParserContainsString($expected, $content);
    }

    private function assertParserContainsString(string $expected, string $content): void
    {
        $parser = new HtmlWordParser();
        $actual = $parser->parse($content);
        self::assertStringContainsString($expected, $actual);
    }
}
