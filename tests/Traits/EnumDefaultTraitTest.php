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

use App\Enums\Theme;
use App\Tests\Data\NoDefaultEnum;
use App\Traits\EnumDefaultTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnumDefaultTrait::class)]
class EnumDefaultTraitTest extends TestCase
{
    public function testDefault(): void
    {
        $actual = Theme::getDefault();
        self::assertSame(Theme::AUTO, $actual);
    }

    public function testNoDefault(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('No default value found for "App\Tests\Data\NoDefaultEnum" enumeration.');
        NoDefaultEnum::getDefault();
    }
}
