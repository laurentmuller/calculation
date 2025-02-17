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

use App\Parameter\OptionsParameter;

/**
 * @extends ParameterTestCase<OptionsParameter>
 */
class OptionsParameterTest extends ParameterTestCase
{
    #[\Override]
    public static function getParameterNames(): \Generator
    {
        yield ['printAddress', 'print_address'];
        yield ['qrCode', 'qr_code'];
    }

    #[\Override]
    public static function getParameterValues(): \Generator
    {
        yield ['printAddress', false];
        yield ['qrCode', false];
    }

    public function testDefaultValue(): void
    {
        self::assertFalse($this->parameter->isPrintAddress());
        self::assertFalse($this->parameter->isQrCode());

        self::assertSame('parameter_option', $this->parameter::getCacheKey());
    }

    public function testSetValue(): void
    {
        $this->parameter->setPrintAddress(true);
        self::assertTrue($this->parameter->isPrintAddress());
        $this->parameter->setQrCode(true);
        self::assertTrue($this->parameter->isPrintAddress());
    }

    #[\Override]
    protected function createParameter(): OptionsParameter
    {
        return new OptionsParameter();
    }
}
