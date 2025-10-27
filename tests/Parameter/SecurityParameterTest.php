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

use App\Enums\StrengthLevel;
use App\Parameter\SecurityParameter;

/**
 * @extends ParameterTestCase<SecurityParameter>
 */
final class SecurityParameterTest extends ParameterTestCase
{
    #[\Override]
    public static function getParameterNames(): \Generator
    {
        yield ['captcha', 'security_captcha'];
        yield ['caseDiff', 'security_case_diff'];
        yield ['compromised', 'security_compromised'];
        yield ['email', 'security_email'];
        yield ['letter', 'security_letter'];
        yield ['level', 'security_level'];
        yield ['number', 'security_number'];
        yield ['specialChar', 'security_special_char'];
    }

    #[\Override]
    public static function getParameterValues(): \Generator
    {
        yield ['captcha', false];
        yield ['caseDiff', false];
        yield ['compromised', false];
        yield ['email', false];
        yield ['letter', false];
        yield ['level', StrengthLevel::NONE];
        yield ['number', false];
        yield ['specialChar', false];
    }

    public function testDefaultValue(): void
    {
        self::assertFalse($this->parameter->isCaptcha());
        self::assertFalse($this->parameter->isCaseDiff());
        self::assertFalse($this->parameter->isCompromised());
        self::assertFalse($this->parameter->isEmail());
        self::assertFalse($this->parameter->isLetter());
        self::assertSame(StrengthLevel::NONE, $this->parameter->getLevel());
        self::assertFalse($this->parameter->isNumber());
        self::assertFalse($this->parameter->isSpecialChar());

        self::assertSame('parameter_security', $this->parameter::getCacheKey());
    }

    public function testSetValue(): void
    {
        $this->parameter->setCaptcha(true);
        self::assertTrue($this->parameter->isCaptcha());
        $this->parameter->setCaseDiff(true);
        self::assertTrue($this->parameter->isCaseDiff());
        $this->parameter->setCompromised(true);
        self::assertTrue($this->parameter->isCompromised());
        $this->parameter->setEmail(true);
        self::assertTrue($this->parameter->isEmail());
        $this->parameter->setLetter(true);
        self::assertTrue($this->parameter->isEmail());
        $this->parameter->setLevel(StrengthLevel::VERY_STRONG);
        self::assertSame(StrengthLevel::VERY_STRONG, $this->parameter->getLevel());
        $this->parameter->setNumber(true);
        self::assertTrue($this->parameter->isNumber());
        $this->parameter->setSpecialChar(true);
        self::assertTrue($this->parameter->isSpecialChar());
    }

    #[\Override]
    protected function createParameter(): SecurityParameter
    {
        return new SecurityParameter();
    }
}
