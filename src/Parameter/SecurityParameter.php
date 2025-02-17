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

namespace App\Parameter;

use App\Attribute\Parameter;
use App\Enums\StrengthLevel;

/**
 * Security parameter.
 */
class SecurityParameter implements ParameterInterface
{
    #[Parameter('security_captcha', false)]
    private bool $captcha = false;

    #[Parameter('security_case_diff', false)]
    private bool $caseDiff = false;

    #[Parameter('security_compromised', false)]
    private bool $compromised = false;

    #[Parameter('security_email', false)]
    private bool $email = false;

    #[Parameter('security_letter', false)]
    private bool $letter = false;

    #[Parameter('security_level', StrengthLevel::NONE)]
    private StrengthLevel $level = StrengthLevel::NONE;

    #[Parameter('security_number', false)]
    private bool $number = false;

    #[Parameter('security_special_char', false)]
    private bool $specialChar = false;

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'parameter_security';
    }

    public function getLevel(): StrengthLevel
    {
        return $this->level;
    }

    public function isCaptcha(): bool
    {
        return $this->captcha;
    }

    public function isCaseDiff(): bool
    {
        return $this->caseDiff;
    }

    public function isCompromised(): bool
    {
        return $this->compromised;
    }

    public function isEmail(): bool
    {
        return $this->email;
    }

    public function isLetter(): bool
    {
        return $this->letter;
    }

    public function isNumber(): bool
    {
        return $this->number;
    }

    public function isSpecialChar(): bool
    {
        return $this->specialChar;
    }

    public function setCaptcha(bool $captcha): self
    {
        $this->captcha = $captcha;

        return $this;
    }

    public function setCaseDiff(bool $caseDiff): self
    {
        $this->caseDiff = $caseDiff;

        return $this;
    }

    public function setCompromised(bool $compromised): self
    {
        $this->compromised = $compromised;

        return $this;
    }

    public function setEmail(bool $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setLetter(bool $letter): self
    {
        $this->letter = $letter;

        return $this;
    }

    public function setLevel(StrengthLevel $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function setNumber(bool $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function setSpecialChar(bool $specialChar): self
    {
        $this->specialChar = $specialChar;

        return $this;
    }
}
