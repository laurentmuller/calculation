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

namespace App\Tests\Enums;

use App\Enums\Importance;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Unit test for the {@link Importance} enumeration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ImportanceTest extends TypeTestCase
{
    public function testCount(): void
    {
        self::assertCount(4, Importance::cases());
        self::assertCount(4, Importance::sorted());
    }

    public function testDefault(): void
    {
        $default = Importance::getDefault();
        $expected = Importance::LOW;
        self::assertEquals($expected, $default);
    }

    public function testLabel(): void
    {
        self::assertEquals('importance.high', Importance::HIGH->getReadable());
        self::assertEquals('importance.low', Importance::LOW->getReadable());
        self::assertEquals('importance.medium', Importance::MEDIUM->getReadable());
        self::assertEquals('importance.urgent', Importance::URGENT->getReadable());
    }

    public function testSorted(): void
    {
        $expected = [
            Importance::LOW,
            Importance::MEDIUM,
            Importance::HIGH,
            Importance::URGENT,
        ];
        $sorted = Importance::sorted();
        self::assertEquals($expected, $sorted);
    }

    public function testValue(): void
    {
        self::assertEquals('high', Importance::HIGH->value);
        self::assertEquals('low', Importance::LOW->value);
        self::assertEquals('medium', Importance::MEDIUM->value);
        self::assertEquals('urgent', Importance::URGENT->value);
    }
}
