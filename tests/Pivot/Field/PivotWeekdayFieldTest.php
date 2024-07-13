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

use App\Pivot\Field\PivotWeekdayField;
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;

class PivotWeekdayFieldTest extends TestCase
{
    public function testConstructor(): void
    {
        $field = new PivotWeekdayField('name');
        self::assertSame('name', $field->getName());
    }

    public function testGetDisplayValue(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);

        $field = new PivotWeekdayField('name');
        $actual = $field->getDisplayValue(null);
        self::assertNull($actual);

        /** @psalm-var string $actual */
        $actual = $field->getDisplayValue(2);
        self::assertSame('Mardi', $actual);

        $field = new PivotWeekdayField('name', short: true);

        /** @psalm-var string $actual */
        $actual = $field->getDisplayValue(2);
        self::assertSame('Mar.', $actual);

        /** @psalm-var int $actual */
        $actual = $field->getDisplayValue(-1);
        self::assertSame(-1, $actual);
    }
}
