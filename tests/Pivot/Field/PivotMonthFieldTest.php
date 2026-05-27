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

use App\Pivot\Field\PivotMonthField;
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;

final class PivotMonthFieldTest extends TestCase
{
    public function testGetDisplayValue(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);

        $field = new PivotMonthField('name');
        $actual = $field->getDisplayValue(7);
        self::assertSame('Juillet', $actual);
    }

    public function testInvalidValue(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Invalid value: 0, allowed values [1..12].');
        $field = new PivotMonthField('name');
        $field->getDisplayValue(0);
    }
}
