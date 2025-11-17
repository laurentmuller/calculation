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
use App\Pivot\Field\PivotMonthField;
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;

final class PivotMonthFieldTest extends TestCase
{
    public function testConstructor(): void
    {
        $field = new PivotMonthField('name', PivotDateField::PART_MONTH);
        self::assertSame('name', $field->getName());
    }

    public function testGetDisplayValue(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);

        $field = new PivotMonthField('name');
        $actual = $field->getDisplayValue(null);
        self::assertNull($actual);

        $actual = $field->getDisplayValue(7);
        self::assertSame('Juillet', $actual);

        $field = new PivotMonthField('name', short: true);
        $actual = $field->getDisplayValue(7);
        self::assertSame('Juil.', $actual);

        $actual = $field->getDisplayValue(-1);
        self::assertSame(-1, $actual);
    }
}
