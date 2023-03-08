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

namespace App\Tests\Attribute;

use App\Attribute\SortableField;
use App\Interfaces\SortModeInterface;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(SortableField::class)]
class SortableFieldTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testSortAscending(): void
    {
        $testedClass = new class() {
            #[SortableField]
            public string $field = '';
        };

        $actual = SortableField::getOrder($testedClass, 'field');
        $expected = SortModeInterface::SORT_ASC;
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSortDescending(): void
    {
        $testedClass = new class() {
            #[SortableField(SortModeInterface::SORT_DESC)]
            public string $field = '';
        };

        $actual = SortableField::getOrder($testedClass, 'field');
        $expected = SortModeInterface::SORT_DESC;
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSortNull(): void
    {
        $testedClass = new class() {
            public string $field = '';
        };

        $actual = SortableField::getOrder($testedClass, 'field');
        self::assertNull($actual);
    }
}
