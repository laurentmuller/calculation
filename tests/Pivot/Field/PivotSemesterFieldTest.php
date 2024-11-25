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

use App\Pivot\Field\PivotSemesterField;
use PHPUnit\Framework\TestCase;

class PivotSemesterFieldTest extends TestCase
{
    public function testConstructor(): void
    {
        $field = new PivotSemesterField('name');
        self::assertSame('name', $field->getName());
    }

    public function testFormatter(): void
    {
        $formatter = fn (int $semester): string => (string) $semester;
        $field = new PivotSemesterField('name');
        $field->setFormatter($formatter);
        self::assertSame($formatter, $field->getFormatter());

        $actual = $field->getDisplayValue(1);
        self::assertSame('1', $actual);
    }

    public function testGetDisplayValue(): void
    {
        $field = new PivotSemesterField('name');

        $actual = $field->getDisplayValue(1);
        self::assertSame('1st semester', $actual);

        $actual = $field->getDisplayValue(2);
        self::assertSame('2nd semester', $actual);

        $actual = $field->getDisplayValue(3);
        self::assertSame('3', $actual);
    }

    public function testGetValue(): void
    {
        $field = new PivotSemesterField('name');
        $actual = $field->getValue([]);
        self::assertNull($actual);

        $date = new \DateTime('2024-04-01');
        $row = ['name' => $date];
        $actual = $field->getValue($row);
        self::assertSame(1, $actual);
    }
}
