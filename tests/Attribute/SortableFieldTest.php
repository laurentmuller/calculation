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
use App\Enums\SortMode;
use PHPUnit\Framework\TestCase;

final class SortableFieldTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testSortAscending(): void
    {
        $testedClass = new class {
            #[SortableField]
            public string $field = '';
        };

        $actual = SortableField::getDirection($testedClass, 'field');
        self::assertSame(SortMode::ASC, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSortDescending(): void
    {
        $testedClass = new class {
            #[SortableField(SortMode::DESC)]
            public string $field = '';
        };

        $actual = SortableField::getDirection($testedClass, 'field');
        self::assertSame(SortMode::DESC, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSortNotFound(): void
    {
        $testedClass = new class {
            public string $field = '';
        };

        $actual = SortableField::getDirection($testedClass, 'fake');
        self::assertNull($actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSortNull(): void
    {
        $testedClass = new class {
            public string $field = '';
        };

        $actual = SortableField::getDirection($testedClass, 'field');
        self::assertNull($actual);
    }
}
