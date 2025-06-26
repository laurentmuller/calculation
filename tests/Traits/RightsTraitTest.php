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

use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Tests\FlagBagTestCase;
use App\Traits\RightsTrait;
use App\Traits\RoleTrait;
use Elao\Enum\FlagBag;

class RightsTraitTest extends FlagBagTestCase implements RoleInterface
{
    use RightsTrait;
    use RoleTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->rights = null;
    }

    public function testOverwrite(): void
    {
        self::assertFalse($this->isOverwrite());
        $this->setOverwrite(true);
        self::assertTrue($this->isOverwrite());
    }

    public function testPermissionEmpty(): void
    {
        $expected = new FlagBag(EntityPermission::class);
        $this->setPermission(EntityName::CALCULATION, $expected);
        $actual = $this->getPermission(EntityName::CALCULATION);
        self::assertSameFlagBag($expected, $actual);
    }

    public function testPermissionShow(): void
    {
        $expected = EntityPermission::SHOW->value;
        $permission = new FlagBag(EntityPermission::class, $expected);
        $this->setPermission(EntityName::CALCULATION, $permission);
        $actual = $this->getPermission(EntityName::CALCULATION)->getValue();
        self::assertSame($expected, $actual);
    }
}
