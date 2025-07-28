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

use App\Pivot\Field\PivotQuarterField;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

class PivotQuarterFieldTest extends TestCase
{
    public function testConstructor(): void
    {
        $field = new PivotQuarterField('name');
        self::assertSame('name', $field->getName());
    }

    public function testFormatter(): void
    {
        $formatter = static fn (int $quarter): string => (string) $quarter;
        $field = new PivotQuarterField('name');
        $field->setFormatter($formatter);
        self::assertSame($formatter, $field->getFormatter());

        $actual = $field->getDisplayValue(1);
        self::assertSame('1', $actual);
    }

    public function testGetDisplayValue(): void
    {
        $field = new PivotQuarterField('name');

        $actual = $field->getDisplayValue(1);
        self::assertSame('1st quarter', $actual);

        $actual = $field->getDisplayValue(2);
        self::assertSame('2nd quarter', $actual);

        $actual = $field->getDisplayValue(3);
        self::assertSame('3rd quarter', $actual);

        $actual = $field->getDisplayValue(4);
        self::assertSame('4th quarter', $actual);

        $actual = $field->getDisplayValue(5);
        self::assertSame('5', $actual);
    }

    public function testGetValue(): void
    {
        $field = new PivotQuarterField('name');
        $actual = $field->getValue([]);
        self::assertNull($actual);

        $date = new DatePoint('2024-04-01');
        $row = ['name' => $date];
        $actual = $field->getValue($row);
        self::assertSame(2, $actual);
    }
}
