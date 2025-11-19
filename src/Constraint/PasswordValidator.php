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

namespace App\Constraint;

use App\Utils\StringUtils;
use Symfony\Component\Validator\Constraint;

/**
 * Password constraint validator.
 *
 * @extends AbstractConstraintValidator<Password>
 */
class PasswordValidator extends AbstractConstraintValidator
{
    public function __construct()
    {
        parent::__construct(Password::class);
    }

    #[\Override]
    public function validate(#[\SensitiveParameter] mixed $value, Constraint $constraint): void
    {
        parent::validate($value, $constraint);
    }

    /**
     * @param Password $constraint
     */
    #[\Override]
    protected function doValidate(#[\SensitiveParameter] string $value, Constraint $constraint): void
    {
        if ($constraint->all) {
            $this->checkAll($value, $constraint);
        } else {
            $this->checkAny($value, $constraint);
        }
    }

    /**
     * Adds a violation.
     */
    private function addViolation(string $message, string $value, string $code): true
    {
        $this->context->buildViolation($message)
            ->setInvalidValue($value)
            ->setCode($code)
            ->addViolation();

        return true;
    }

    /**
     * Check all constraints.
     */
    private function checkAll(string $value, Password $constraint): void
    {
        $this->checkLetters($constraint, $value);
        $this->checkCaseDiff($constraint, $value);
        $this->checkNumber($constraint, $value);
        $this->checkSpecialChar($constraint, $value);
        $this->checkEmail($constraint, $value);
    }

    /**
     * Check constraints until a violation is found.
     */
    private function checkAny(string $value, Password $constraint): void
    {
        if (!($this->checkLetters($constraint, $value)
            || $this->checkCaseDiff($constraint, $value)
            || $this->checkNumber($constraint, $value)
            || $this->checkSpecialChar($constraint, $value))) {
            $this->checkEmail($constraint, $value);
        }
    }

    /**
     * Checks the presence of lower/upper character.
     */
    private function checkCaseDiff(Password $constraint, string $value): bool
    {
        return $this->validateRegex(
            $constraint->caseDiff,
            '/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u',
            $value,
            $constraint->caseDiffMessage,
            Password::CASE_DIFF_ERROR
        );
    }

    /**
     * Checks if the value is an e-mail.
     */
    private function checkEmail(Password $constraint, string $value): void
    {
        if ($constraint->email && false !== \filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            $this->addViolation($constraint->emailMessage, $value, Password::EMAIL_ERROR);
        }
    }

    /**
     * Checks the presence of letter character.
     */
    private function checkLetters(Password $constraint, string $value): bool
    {
        return $this->validateRegex(
            $constraint->letter,
            '/\pL/u',
            $value,
            $constraint->letterMessage,
            Password::LETTER_ERROR
        );
    }

    /**
     * Checks the presence of one or more numbers characters.
     */
    private function checkNumber(Password $constraint, string $value): bool
    {
        return $this->validateRegex(
            $constraint->number,
            '/\pN/u',
            $value,
            $constraint->numberMessage,
            Password::NUMBER_ERROR
        );
    }

    /**
     * Checks the presence of one or more special characters.
     */
    private function checkSpecialChar(Password $constraint, string $value): bool
    {
        return $this->validateRegex(
            $constraint->specialChar,
            '/[^p{Ll}\p{Lu}\pL\pN]/u',
            $value,
            $constraint->specialCharMessage,
            Password::SPECIAL_CHAR_ERROR
        );
    }

    /**
     * @param non-empty-string $pattern
     */
    private function validateRegex(bool $enabled, string $pattern, string $value, string $message, string $code): bool
    {
        if ($enabled && !StringUtils::pregMatch($pattern, $value)) {
            return $this->addViolation($message, $value, $code);
        }

        return false;
    }
}
