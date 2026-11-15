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

namespace App\Tests\Doctrine;

use App\Doctrine\FixedFloatType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FixedFloatTypeTest extends TestCase
{
    public static function getValues(): \Generator
    {
        yield [null, 0.0];
        yield [0.0, 0.0];
        yield [0, 0.0];
        yield ['', 0.0];
        yield ['a', 0.0];
        yield [10, 10.0];
        yield [-10, -10.0];
        yield ['10', 10.0];
        yield ['10.5', 10.5];
        yield ['10.51', 10.51];
    }

    /**
     * @throws Exception
     */
    #[DataProvider('getValues')]
    public function testConvertToDatabaseValue(mixed $value, float $expected): void
    {
        $platform = new MariaDBPlatform();
        $type = new FixedFloatType();
        $actual = $type->convertToDatabaseValue($value, $platform);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    #[DataProvider('getValues')]
    public function testConvertToPHPValue(mixed $value, float $expected): void
    {
        $platform = new MariaDBPlatform();
        $type = new FixedFloatType();
        $actual = $type->convertToPHPValue($value, $platform);
        self::assertSame($expected, $actual);
    }

    public function testSQLDeclaration(): void
    {
        $platform = new MariaDBPlatform();
        $type = new FixedFloatType();
        $actual = $type->getSQLDeclaration([], $platform);
        $expected = "DOUBLE PRECISION DEFAULT '0'";
        self::assertSame($expected, $actual);
    }
}
