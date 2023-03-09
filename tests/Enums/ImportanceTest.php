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

#[\PHPUnit\Framework\Attributes\CoversClass(Importance::class)]
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
        self::assertSame($expected, $default);
    }

    public function testLabel(): void
    {
        self::assertSame('importance.high', Importance::HIGH->getReadable());
        self::assertSame('importance.low', Importance::LOW->getReadable());
        self::assertSame('importance.medium', Importance::MEDIUM->getReadable());
        self::assertSame('importance.urgent', Importance::URGENT->getReadable());
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
        self::assertSame($expected, $sorted);
    }

    public function testValue(): void
    {
        self::assertSame('high', Importance::HIGH->value);
        self::assertSame('low', Importance::LOW->value);
        self::assertSame('medium', Importance::MEDIUM->value);
        self::assertSame('urgent', Importance::URGENT->value);
    }
}
