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

use App\Pivot\Field\PivotDateField;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

class PivotDateFieldTest extends TestCase
{
    public function testConstructor(): void
    {
        $field = new PivotDateField('name', PivotDateField::PART_MONTH);
        self::assertSame('name', $field->getName());
    }

    public function testGetValue(): void
    {
        $field = new PivotDateField('name', PivotDateField::PART_MONTH);
        $actual = $field->getValue([]);
        self::assertNull($actual);

        $date = new DatePoint('2024-03-01');
        $row = ['name' => $date];
        $actual = $field->getValue($row);
        self::assertSame(3, $actual);
    }
}
