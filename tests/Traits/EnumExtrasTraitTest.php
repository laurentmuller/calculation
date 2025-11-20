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

namespace App\Tests\Traits;

use App\Tests\Fixture\FixtureEnumExtra;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EnumExtrasTraitTest extends TestCase
{
    public static function getExtraBool(): \Generator
    {
        yield [FixtureEnumExtra::DEFAULT, 'bool'];
        yield [FixtureEnumExtra::DEFAULT, 'bool_on'];
        yield [FixtureEnumExtra::DEFAULT, 'bool_yes'];
        yield [FixtureEnumExtra::DEFAULT, 'bool_true'];
        yield [FixtureEnumExtra::DEFAULT, 'int'];
        yield [FixtureEnumExtra::DEFAULT, 'float'];
        yield [FixtureEnumExtra::DEFAULT, 'enum', false];
        yield [FixtureEnumExtra::DEFAULT, 'string', false];
        yield [FixtureEnumExtra::DEFAULT, 'fake-key', false];
    }

    public static function getExtraEnum(): \Generator
    {
        yield [FixtureEnumExtra::DEFAULT, 'enum', FixtureEnumExtra::DEFAULT];
        yield [FixtureEnumExtra::DEFAULT, 'fake-key', FixtureEnumExtra::DEFAULT];
    }

    public static function getExtraFloat(): \Generator
    {
        yield [FixtureEnumExtra::DEFAULT, 'float'];
        yield [FixtureEnumExtra::DEFAULT, 'float_numeric'];
        yield [FixtureEnumExtra::DEFAULT, 'int'];
        yield [FixtureEnumExtra::DEFAULT, 'int_numeric'];
        yield [FixtureEnumExtra::DEFAULT, 'fake-key', 0.0];
    }

    public static function getExtraInt(): \Generator
    {
        yield [FixtureEnumExtra::DEFAULT, 'int'];
        yield [FixtureEnumExtra::DEFAULT, 'int_numeric'];
        yield [FixtureEnumExtra::DEFAULT, 'float'];
        yield [FixtureEnumExtra::DEFAULT, 'float_numeric'];
        yield [FixtureEnumExtra::DEFAULT, 'fake-key', 0];
    }

    public static function getExtraString(): \Generator
    {
        yield [FixtureEnumExtra::DEFAULT, 'string', 'string'];
        yield [FixtureEnumExtra::DEFAULT, 'fake-key', ''];
    }

    #[DataProvider('getExtraBool')]
    public function testExtraBool(FixtureEnumExtra $fixture, string $key, bool $expected = true): void
    {
        $actual = $fixture->getExtraBool($key);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getExtraEnum')]
    public function testExtraEnum(FixtureEnumExtra $fixture, string $key, FixtureEnumExtra $expected): void
    {
        $actual = $fixture->getExtraEnum($key, $expected);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getExtraFloat')]
    public function testExtraFloat(FixtureEnumExtra $fixture, string $key, float $expected = 1.0): void
    {
        $actual = $fixture->getExtraFloat($key);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getExtraInt')]
    public function testExtraInt(FixtureEnumExtra $fixture, string $key, int $expected = 1): void
    {
        $actual = $fixture->getExtraInt($key);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getExtraString')]
    public function testExtraString(FixtureEnumExtra $fixture, string $key, string $expected): void
    {
        $actual = $fixture->getExtraString($key);
        self::assertSame($expected, $actual);
    }
}
