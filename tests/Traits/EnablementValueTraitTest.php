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

use App\Traits\EnablementValueTrait;
use PHPUnit\Framework\TestCase;

final class EnablementValueTraitTest extends TestCase
{
    use EnablementValueTrait;

    public function testIsDisabledValue(): void
    {
        self::assertFalse($this->isDisabledValue('none'));
        self::assertFalse($this->isDisabledValue('no value'));
        self::assertFalse($this->isDisabledValue('on'));

        self::assertTrue($this->isDisabledValue('DISABLED'));
        self::assertTrue($this->isDisabledValue('off'));
    }

    public function testIsEnabledValue(): void
    {
        self::assertFalse($this->isEnabledValue('none'));
        self::assertFalse($this->isEnabledValue('no value'));
        self::assertFalse($this->isEnabledValue('off'));

        self::assertTrue($this->isEnabledValue('ENABLED'));
        self::assertTrue($this->isEnabledValue('on'));
    }

    public function testIsNoneValue(): void
    {
        self::assertFalse($this->isNoneValue('no value'));
        self::assertFalse($this->isNoneValue('off'));
        self::assertFalse($this->isNoneValue('on'));

        self::assertTrue($this->isNoneValue('NONE'));
        self::assertTrue($this->isNoneValue('None'));
    }

    public function testIsNoValue(): void
    {
        self::assertFalse($this->isNoValue('none'));
        self::assertFalse($this->isNoValue('off'));
        self::assertFalse($this->isNoValue('on'));

        self::assertTrue($this->isNoValue('NO VALUE'));
        self::assertTrue($this->isNoValue('No Value'));
    }
}
