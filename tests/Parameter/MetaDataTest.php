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
        $data = new MetaData('name', 'property', 'array', null);
        self::assertFalse($data->isEnumTypeInt());
        self::assertFalse($data->isEnumTypeString());
        self::assertFalse($data->isEntityInterfaceType());
    }

    public function testIsEntity(): void
    {
        $data = new MetaData('name', 'property', User::class, null);
        self::assertFalse($data->isEnumTypeInt());
        self::assertFalse($data->isEnumTypeString());
        self::assertTrue($data->isEntityInterfaceType());
    }

    public function testIsEnumTypeInt(): void
    {
        $data = new MetaData('name', 'property', StrengthLevel::class, null);
        self::assertTrue($data->isEnumTypeInt());
        self::assertFalse($data->isEnumTypeString());
        self::assertFalse($data->isEntityInterfaceType());
    }

    public function testIsEnumTypeString(): void
    {
        $data = new MetaData('name', 'property', MessagePosition::class, null);
        self::assertFalse($data->isEnumTypeInt());
        self::assertTrue($data->isEnumTypeString());
        self::assertFalse($data->isEntityInterfaceType());
    }

    public function testIsNotBackedEnum(): void
    {
        $data = new MetaData('name', 'property', PdfTextAlignment::class, null);
        self::assertFalse($data->isEnumTypeInt());
        self::assertFalse($data->isEnumTypeString());
        self::assertFalse($data->isEntityInterfaceType());
    }
}
