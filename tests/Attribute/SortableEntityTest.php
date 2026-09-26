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

use App\Attribute\SortableEntity;
use App\Enums\SortMode;
use PHPUnit\Framework\TestCase;

#[SortableEntity(name: 'descending', direction: SortMode::DESC)]
#[SortableEntity(name: 'ascending')]
final class SortableEntityTest extends TestCase
{
    public string $ascending = '';

    /**
     * @throws \ReflectionException
     */
    public function testMultipleSort(): void
    {
        $actual = SortableEntity::getOrder($this);

        self::assertCount(2, $actual);
        self::assertArrayHasKey('ascending', $actual);
        self::assertArrayHasKey('descending', $actual);
        self::assertSame(SortMode::ASC, $actual['ascending']);
        self::assertSame(SortMode::DESC, $actual['descending']);

        // test order
        $first = \reset($actual);
        self::assertSame(SortMode::DESC, $first);
    }

    /**
     * @throws \ReflectionException
     */
    public function testNoSort(): void
    {
        $actual = SortableEntity::getOrder(new \stdClass());
        self::assertEmpty($actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testNotExist(): void
    {
        $this->expectException(\ReflectionException::class);
        $this->expectExceptionMessage('The property "descending" is not defined in "App\Tests\Attribute\SortableEntityTest"');
        SortableEntity::getOrder($this, true);
    }
}
