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

namespace App\Tests\Parameter;

use App\Entity\User;
use App\Enums\MessagePosition;
use App\Enums\StrengthLevel;
use App\Parameter\MetaData;
use fpdf\Enums\PdfTextAlignment;
use PHPUnit\Framework\TestCase;

class MetaDataTest extends TestCase
{
    public function testDefault(): void
    {
        $actual = new MetaData('name', 'property', 'array', null);
        self::assertSameMetaData($actual);
    }

    public function testIsEntity(): void
    {
        $actual = new MetaData('name', 'property', User::class, null);
        self::assertSameMetaData($actual);
    }

    public function testIsEnumTypeInt(): void
    {
        $actual = new MetaData('name', 'property', StrengthLevel::class, null);
        self::assertSameMetaData($actual, true);
    }

    public function testIsEnumTypeString(): void
    {
        $actual = new MetaData('name', 'property', MessagePosition::class, null);
        self::assertSameMetaData($actual, false, true);
    }

    public function testIsNotBackedEnum(): void
    {
        $actual = new MetaData('name', 'property', PdfTextAlignment::class, null);
        self::assertSameMetaData($actual);
    }

    protected static function assertSameMetaData(
        MetaData $actual,
        bool $expectedEnumInt = false,
        bool $expectedEnumString = false,
    ): void {
        self::assertSame($expectedEnumInt, $actual->isEnumTypeInt());
        self::assertSame($expectedEnumString, $actual->isEnumTypeString());
    }
}
