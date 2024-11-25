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

use App\Enums\MessagePosition;
use App\Enums\StrengthLevel;
use App\Pdf\Html\HtmlAttribute;
use PHPUnit\Framework\TestCase;

class HtmlAttributeTest extends TestCase
{
    /**
     * @throws \DOMException
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public function testGetEnumValueInt(): void
    {
        $expected = StrengthLevel::MEDIUM;
        $document = new \DOMDocument('1.0', 'utf-8');
        $element = $document->createElement('div');
        $element->setAttribute('class', (string) $expected->value);
        $actual = HtmlAttribute::CLASS_NAME->getEnumValue($element, $expected);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \DOMException
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public function testGetEnumValueString(): void
    {
        $expected = MessagePosition::TOP_LEFT;
        $document = new \DOMDocument('1.0', 'utf-8');
        $element = $document->createElement('div');
        $element->setAttribute('class', $expected->value);
        $actual = HtmlAttribute::CLASS_NAME->getEnumValue($element, $expected);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \DOMException
     */
    public function testGetIntValue(): void
    {
        $expected = 12456;
        $document = new \DOMDocument('1.0', 'utf-8');
        $element = $document->createElement('div');
        $element->setAttribute('class', (string) $expected);
        $actual = HtmlAttribute::CLASS_NAME->getIntValue($element);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \DOMException
     */
    public function testGetIntValueWithDefault(): void
    {
        $expected = 12456;
        $document = new \DOMDocument('1.0', 'utf-8');
        $element = $document->createElement('div');
        $element->setAttribute('class', '');
        $actual = HtmlAttribute::CLASS_NAME->getIntValue($element, $expected);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \DOMException
     */
    public function testGetValue(): void
    {
        $expected = 'text-start';
        $document = new \DOMDocument('1.0', 'utf-8');
        $element = $document->createElement('div');
        $element->setAttribute('class', $expected);
        $actual = HtmlAttribute::CLASS_NAME->getValue($element);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \DOMException
     */
    public function testGetValueEmpty(): void
    {
        $document = new \DOMDocument('1.0', 'utf-8');
        $element = $document->createElement('div');
        $element->setAttribute('class', '');
        $actual = HtmlAttribute::CLASS_NAME->getValue($element);
        self::assertNull($actual);
    }

    /**
     * @throws \DOMException
     */
    public function testGetValueNoFound(): void
    {
        $document = new \DOMDocument('1.0', 'utf-8');
        $element = $document->createElement('div');
        $element->setAttribute('fake', 'text-start');
        $actual = HtmlAttribute::CLASS_NAME->getValue($element);
        self::assertNull($actual);
    }

    /**
     * @throws \DOMException
     */
    public function testGetValueWithDefault(): void
    {
        $expected = 'text-start';
        $document = new \DOMDocument('1.0', 'utf-8');
        $element = $document->createElement('div');
        $element->setAttribute('class', '');
        $actual = HtmlAttribute::CLASS_NAME->getValue($element, $expected);
        self::assertSame($expected, $actual);
    }

    public function testNoAttribute(): void
    {
        $document = new \DOMDocument('1.0', 'utf-8');
        $element = $document->createComment('comment');
        $actual = HtmlAttribute::CLASS_NAME->getValue($element);
        self::assertNull($actual);
    }
}
