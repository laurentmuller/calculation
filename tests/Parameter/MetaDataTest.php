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
use App\Parameter\MetaData;
use PHPUnit\Framework\TestCase;

class MetaDataTest extends TestCase
{
    public function testDefault(): void
    {
        $data = new MetaData('name', 'property', 'array', null);
        self::assertFalse($data->isBackedEnumType());
        self::assertFalse($data->isEntityInterfaceType());
    }

    public function testIsBackEnum(): void
    {
        $data = new MetaData('name', 'property', MessagePosition::class, null);
        self::assertTrue($data->isBackedEnumType());
        self::assertFalse($data->isEntityInterfaceType());
    }

    public function testIsEntity(): void
    {
        $data = new MetaData('name', 'property', User::class, null);
        self::assertFalse($data->isBackedEnumType());
        self::assertTrue($data->isEntityInterfaceType());
    }
}
