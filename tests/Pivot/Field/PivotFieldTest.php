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
use App\Pivot\Field\PivotMethod;
use PHPUnit\Framework\TestCase;

class PivotFieldTest extends TestCase
{
    public function testConstructor(): void
    {
        $field = new PivotField('name', 'title', PivotMethod::FLOAT);
        self::assertSame('name', $field->getName());
        self::assertSame('title', $field->getTitle());
        self::assertSame(PivotMethod::FLOAT, $field->getMethod());
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

        $field = new PivotField('name', method: PivotMethod::STRING);
        $actual = $field->getValue(['name' => 10.0]);
        self::assertSame('10', $actual);

        $field = new PivotField('name', method: PivotMethod::FLOAT);
        $actual = $field->getValue(['name' => 10.0]);
        self::assertSame(10.0, $actual);

        $field = new PivotField('name', method: PivotMethod::INTEGER);
        $actual = $field->getValue(['name' => 10.0]);
        self::assertSame(10, $actual);
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
        $field->setTitle('title');
        $actual = $field->jsonSerialize();
        self::assertSame($expected, $actual);
    }

    public function testSetMethod(): void
    {
        $field = new PivotField('name');
        $field->setMethod(PivotMethod::INTEGER);
        self::assertSame(PivotMethod::INTEGER, $field->getMethod());
    }

    public function testSetTitle(): void
    {
        $field = new PivotField('name');
        self::assertNull($field->getTitle());

        $field->setTitle('title');
        self::assertSame('title', $field->getTitle());
    }
}
