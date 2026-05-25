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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PivotWeekdayFieldTest extends TestCase
{
    public static function getLongWeekdays(): \Generator
    {
        yield [1, 'Lundi'];
        yield [2, 'Mardi'];
        yield [3, 'Mercredi'];
        yield [4, 'Jeudi'];
        yield [5, 'Vendredi'];
        yield [6, 'Samedi'];
        yield [7, 'Dimanche'];
    }

    public function testInvalidValue(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Invalid weekday value: 10, allowed value [1..7].');
        $field = new PivotWeekdayField('name');
        $field->getDisplayValue(10);
    }

    #[DataProvider('getLongWeekdays')]
    public function testLongWeekdays(int $value, string $expected): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $field = new PivotWeekdayField('name');
        $actual = $field->getDisplayValue($value);
        self::assertSame($expected, $actual);
    }
}
