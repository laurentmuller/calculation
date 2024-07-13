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

use App\Pivot\Field\PivotFieldFactory;
use App\Pivot\Field\PivotMethod;
use PHPUnit\Framework\TestCase;

class PivotFieldFactoryTest extends TestCase
{
    public function testDefault(): void
    {
        $field = PivotFieldFactory::default('name');
        self::assertSame('name', $field->getName());
        self::assertNull($field->getTitle());
        self::assertSame(PivotMethod::STRING, $field->getMethod());
    }

    public function testFloat(): void
    {
        $field = PivotFieldFactory::float('name');
        self::assertSame('name', $field->getName());
        self::assertNull($field->getTitle());
        self::assertSame(PivotMethod::FLOAT, $field->getMethod());
    }

    public function testInteger(): void
    {
        $field = PivotFieldFactory::integer('name');
        self::assertSame('name', $field->getName());
        self::assertNull($field->getTitle());
        self::assertSame(PivotMethod::INTEGER, $field->getMethod());
    }

    public function testMonth(): void
    {
        $field = PivotFieldFactory::month('name');
        self::assertSame('name', $field->getName());
    }

    public function testQuarter(): void
    {
        $field = PivotFieldFactory::quarter('name');
        self::assertSame('name', $field->getName());
    }

    public function testSemester(): void
    {
        $field = PivotFieldFactory::semester('name');
        self::assertSame('name', $field->getName());
    }

    public function testWeek(): void
    {
        $field = PivotFieldFactory::week('name');
        self::assertSame('name', $field->getName());
    }

    public function testWeekday(): void
    {
        $field = PivotFieldFactory::weekday('name');
        self::assertSame('name', $field->getName());
    }

    public function testYear(): void
    {
        $field = PivotFieldFactory::year('name');
        self::assertSame('name', $field->getName());
    }
}
