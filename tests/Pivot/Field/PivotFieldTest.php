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

namespace App\Tests\Pivot\Field;

use App\Pivot\Field\PivotField;
use App\Pivot\Formatter\TranslateFormatter;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;

final class PivotFieldTest extends TestCase
{
    use TranslatorMockTrait;

    public function testConstructor(): void
    {
        $field = new PivotField('name', 'title');
        self::assertSame('name', $field->getName());
        self::assertSame('title', $field->getTitle());
    }

    public function testGetDisplayValue(): void
    {
        $expected = 'value';
        $field = new PivotField('name');
        $actual = $field->getDisplayValue($expected);
        self::assertSame($expected, $actual);
    }

    public function testGetValue(): void
    {
        $field = new PivotField('name');
        $actual = $field->getValue([]);
        self::assertNull($actual);

        $field = new PivotField('name', title: 'title');
        $actual = $field->getValue(['name' => 10.0]);
        self::assertSame(10.0, $actual);
    }

    public function testJsonSerialize(): void
    {
        $field = new PivotField('name');
        $expected = ['name' => 'name'];
        $actual = $field->jsonSerialize();
        self::assertSame($expected, $actual);

        $expected = [
            'name' => 'name',
            'title' => 'title',
        ];
        $field = new PivotField('name', 'title');
        $actual = $field->jsonSerialize();
        self::assertSame($expected, $actual);
    }

    public function testTranslateFormatter(): void
    {
        $translator = $this->createMockTranslator();
        $formatter = new TranslateFormatter($translator, 'fake', 'fake');
        $field = new PivotField('name', formatter: $formatter);
        $actual = $field->getDisplayValue('value');
        self::assertSame('fake', $actual);
    }
}
