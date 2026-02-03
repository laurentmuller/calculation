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

final class MetaDataTest extends TestCase
{
    public function testDefault(): void
    {
        $actual = $this->createMetaData('array');
        $this->assertSameMetaData($actual);
    }

    public function testIsEntity(): void
    {
        $actual = $this->createMetaData(User::class);
        $this->assertSameMetaData($actual);
    }

    public function testIsEnumTypeInt(): void
    {
        $actual = $this->createMetaData(StrengthLevel::class);
        $this->assertSameMetaData($actual, true);
    }

    public function testIsEnumTypeString(): void
    {
        $actual = $this->createMetaData(MessagePosition::class);
        $this->assertSameMetaData($actual, false, true);
    }

    public function testIsNotBackedEnum(): void
    {
        $actual = $this->createMetaData(PdfTextAlignment::class);
        $this->assertSameMetaData($actual);
    }

    private function assertSameMetaData(
        MetaData $actual,
        bool $expectedEnumInt = false,
        bool $expectedEnumString = false,
    ): void {
        self::assertSame($expectedEnumInt, $actual->isIntEnum());
        self::assertSame($expectedEnumString, $actual->isStringEnum());
    }

    private function createMetaData(string $type): MetaData
    {
        return new MetaData('name', 'property', $type, null);
    }
}
