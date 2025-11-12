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

namespace App\Tests\Pdf\Html;

use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Html\HtmlStyle;
use App\Pdf\Html\HtmlTag;
use fpdf\Enums\PdfFontName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HtmlTagTest extends TestCase
{
    public static function getMatches(): \Generator
    {
        yield [HtmlTag::BODY, 'body', true];
        yield [HtmlTag::BODY, 'BODY', true];
        yield [HtmlTag::BODY, 'fake', false];
    }

    public static function getStyles(): \Generator
    {
        yield [HtmlTag::BODY, null];
        yield [HtmlTag::SPAN, null];
        yield [HtmlTag::TEXT, null];
        yield [HtmlTag::LINE_BREAK, null];
        yield [HtmlTag::LIST_ITEM, null];

        $style = HtmlStyle::default()->setFontBold();
        yield [HtmlTag::BOLD, $style];
        yield [HtmlTag::STRONG, $style];

        $style = HtmlStyle::default()->setFontItalic();
        yield [HtmlTag::ITALIC, $style];
        yield [HtmlTag::EMPHASIS, $style];

        yield [HtmlTag::PARAGRAPH, HtmlStyle::default()->setBottomMargin(2.0)];
        yield [HtmlTag::LIST_ORDERED, HtmlStyle::default()->setBottomMargin(1.0)->setLeftMargin(2.0)];
        yield [HtmlTag::LIST_UNORDERED, HtmlStyle::default()->setBottomMargin(1.0)->setLeftMargin(2.0)];

        $style = HtmlStyle::default()->setFontName(PdfFontName::COURIER);
        yield [HtmlTag::SAMPLE, $style];
        yield [HtmlTag::KEYBOARD, $style];

        yield [HtmlTag::UNDERLINE, HtmlStyle::default()->setFontUnderline()];
        yield [HtmlTag::VARIABLE, HtmlStyle::default()->setFontName(PdfFontName::COURIER)->setFontItalic()];

        $color = new PdfTextColor(255, 0, 0);
        yield [HtmlTag::CODE, HtmlStyle::default()->setFontName(PdfFontName::COURIER)
            ->setTextColor($color)];

        $style = HtmlStyle::default()
            ->setFontBold(true)
            ->setBottomMargin(2.0)
            ->setFontSize(2.5 * 9.0);
        yield [HtmlTag::H1, $style];

        $style = (clone $style)->setFontSize(2.0 * 9.0);
        yield [HtmlTag::H2, $style];

        $style = (clone $style)->setFontSize(1.75 * 9.0);
        yield [HtmlTag::H3, $style];

        $style = (clone $style)->setFontSize(1.5 * 9.0);
        yield [HtmlTag::H4, $style];

        $style = (clone $style)->setFontSize(1.25 * 9.0);
        yield [HtmlTag::H5, $style];

        $style = (clone $style)->setFontSize(1.1 * 9.0);
        yield [HtmlTag::H6, $style];
    }

    public function testFindFirst(): void
    {
        $source = <<<HTML
                <?xml encoding="UTF-8">
                <body>
                    <p>Text</p>
                </body>
            HTML;

        $document = new \DOMDocument();
        $actual = $document->loadHTML($source, \LIBXML_NOERROR | \LIBXML_NOBLANKS);
        self::assertTrue($actual);

        $actual = HtmlTag::BODY->findFirst($document);
        self::assertNotNull($actual);
        $actual = HtmlTag::PARAGRAPH->findFirst($document);
        self::assertNotNull($actual);
        $actual = HtmlTag::CODE->findFirst($document);
        self::assertNull($actual);
    }

    public function testGetStyle(): void
    {
        $actual = HtmlTag::getStyle('body');
        self::assertNull($actual);

        $style = HtmlStyle::default()->setFontBold(true)
            ->setBottomMargin(2.0)
            ->setFontSize(2.5 * 9.0);
        $actual = HtmlTag::getStyle('h1');
        self::assertNotNull($actual);
        $this->assertSameStyle($style, $actual);
    }

    #[DataProvider('getMatches')]
    public function testMatch(HtmlTag $tag, string $value, bool $expected): void
    {
        $actual = $tag->match($value);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getStyles')]
    public function testStyle(HtmlTag $tag, ?HtmlStyle $expected): void
    {
        $this->assertSameStyle($expected, $tag);
    }

    private function assertSameStyle(?HtmlStyle $expected, HtmlTag|HtmlStyle $tagOrStyle): void
    {
        $actual = $tagOrStyle instanceof HtmlStyle ? $tagOrStyle : $tagOrStyle->style();
        if (!$expected instanceof HtmlStyle) {
            self::assertNull($actual);

            return;
        }

        self::assertNotNull($actual);

        self::assertSame($expected->getAlignment(), $actual->getAlignment());
        self::assertSame($expected->getIndent(), $actual->getIndent());
        self::assertSame($expected->getTopMargin(), $actual->getTopMargin());
        self::assertSame($expected->getBottomMargin(), $actual->getBottomMargin());
        self::assertSame($expected->getLeftMargin(), $actual->getLeftMargin());
        self::assertSame($expected->getRightMargin(), $actual->getRightMargin());

        self::assertEqualsCanonicalizing($expected->getBorder(), $actual->getBorder());
        self::assertEqualsCanonicalizing($expected->getDrawColor(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing($expected->getFillColor(), $actual->getFillColor());
        self::assertEqualsCanonicalizing($expected->getTextColor(), $actual->getTextColor());
        self::assertEqualsCanonicalizing($expected->getFont(), $actual->getFont());
        self::assertEqualsCanonicalizing($expected->getLine(), $actual->getLine());
    }
}
