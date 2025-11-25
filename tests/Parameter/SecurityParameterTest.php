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
        self::assertFalse($this->parameter->isNumber());
        self::assertFalse($this->parameter->isSpecialChar());
        self::assertSame(StrengthLevel::NONE, $this->parameter->getLevel());
        self::assertSame('parameter_security', $this->parameter::getCacheKey());
    }

    public function testIsPasswordConstraint(): void
    {
        $actual = $this->parameter->isPasswordConstraint();
        self::assertFalse($actual);
        $this->parameter->setLetter(true);
        $actual = $this->parameter->isPasswordConstraint();
        self::assertTrue($actual);
    }

    public function testIsStrengthConstraint(): void
    {
        $actual = $this->parameter->isStrengthConstraint();
        self::assertFalse($actual);
        $this->parameter->setLevel(StrengthLevel::MEDIUM);
        $actual = $this->parameter->isStrengthConstraint();
        self::assertTrue($actual);
    }

    public function testNotCompromisedConstraint(): void
    {
        $actual = $this->parameter->getNotCompromisedConstraint();
        self::assertSame(1, $actual->threshold);
    }

    public function testPasswordConstraint(): void
    {
        $constraint = $this->parameter->getPasswordConstraint();
        self::assertFalse($constraint->all);
        self::assertFalse($constraint->letter);
        self::assertFalse($constraint->caseDiff);
        self::assertFalse($constraint->number);
        self::assertFalse($constraint->specialChar);
        self::assertFalse($constraint->email);
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

    public function testStrengthConstraint(): void
    {
        $constraint = $this->parameter->getStrengthConstraint();
        self::assertSame(StrengthLevel::NONE, $constraint->minimum);
    }

    #[\Override]
    protected function createParameter(): SecurityParameter
    {
        return new SecurityParameter();
    }
}
