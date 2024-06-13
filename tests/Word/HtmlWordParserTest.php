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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlWordParser::class)]
class HtmlWordParserTest extends TestCase
{
    public function testParseBorder(): void
    {
        $content = '<h4 class="border-top">Objet du contrat</h4>';
        $parser = new HtmlWordParser();
        $actual = $parser->parse($content);
        self::assertStringContainsString('1px #808080 solid;', $actual);
    }

    public function testParseClassEmpty(): void
    {
        $content = '<h4 class="">Objet du contrat</h4>';
        $parser = new HtmlWordParser();
        $actual = $parser->parse($content);
        self::assertStringContainsString('h4', $actual);
    }

    public function testParseEmpty(): void
    {
        $parser = new HtmlWordParser();
        $actual = $parser->parse('');
        self::assertSame('', $actual);
    }

    public function testParseMargins(): void
    {
        $content = '<h4 class="m-2">Objet du contrat</h4>';
        $parser = new HtmlWordParser();
        $actual = $parser->parse($content);
        self::assertStringContainsString('margin:8px;', $actual);
    }

    public function testParseMultiClassEmpty(): void
    {
        $content = '<h4 class="  ">Objet du contrat</h4>';
        $parser = new HtmlWordParser();
        $actual = $parser->parse($content);
        self::assertStringContainsString('h4', $actual);
    }

    public function testParseTextJustify(): void
    {
        $content = '<h4 class="text-justify">Objet du contrat</h4>';
        $parser = new HtmlWordParser();
        $actual = $parser->parse($content);
        self::assertStringContainsString('text-align:justify;', $actual);
    }
}
