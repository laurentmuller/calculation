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

namespace App\Validator;

use App\Utils\FormatUtils;
use Symfony\Component\Validator\Constraint;

/**
 * Password constraint validator.
 *
 * @extends AbstractConstraintValidator<Password>
 */
class PasswordValidator extends AbstractConstraintValidator
{
    private const API_ENDPOINT = 'https://api.pwnedpasswords.com/range/%s';

    public function __construct()
    {
        parent::__construct(Password::class);
    }

    /**
     * @param Password $constraint
     */
    protected function doValidate(string $value, Constraint $constraint): void
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
    private function addViolation(string $message, string $value, string $code, array $parameters = []): true
    {
        $this->context->buildViolation($message)
            ->setParameters($parameters)
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
        $this->checkCompromised($constraint, $value);
    }

    /**
     * Check constraints until a violation is found.
     */
    private function checkAny(string $value, Password $constraint): void
    {
        if (!($this->checkLetters($constraint, $value)
        || $this->checkCaseDiff($constraint, $value)
        || $this->checkNumber($constraint, $value)
        || $this->checkSpecialChar($constraint, $value)
        || $this->checkEmail($constraint, $value))) {
            $this->checkCompromised($constraint, $value);
        }
    }

    /**
     * Checks the presence of lower/upper character.
     */
    private function checkCaseDiff(Password $constraint, string $value): bool
    {
        return $this->validateRegex(
            $constraint->case_diff,
            '/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u',
            $value,
            $constraint->case_diff_message,
            Password::CASE_DIFF_ERROR
        );
    }

    /**
     * Check if the password is compromised.
     */
    private function checkCompromised(Password $constraint, string $value): void
    {
        if (!$constraint->compromised) {
            return;
        }

        $count = $this->getPasswordCount($value);
        if (0 === $count) {
            return;
        }

        $parameters = ['{{count}}' => FormatUtils::formatInt($count)];

        $this->addViolation($constraint->compromised_message, $value, Password::COMPROMISED_ERROR, $parameters);
    }

    /**
     * Checks if the value is an e-mail.
     */
    private function checkEmail(Password $constraint, string $value): bool
    {
        if ($constraint->email && false !== \filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            return $this->addViolation($constraint->email_message, $value, Password::EMAIL_ERROR);
        }

        return false;
    }

    /**
     * Checks the presence of letter character.
     */
    private function checkLetters(Password $constraint, string $value): bool
    {
        return $this->validateRegex(
            $constraint->letters,
            '/\pL/u',
            $value,
            $constraint->letters_message,
            Password::LETTERS_ERROR
        );
    }

    /**
     * Checks the presence of one or more numbers characters.
     */
    private function checkNumber(Password $constraint, string $value): bool
    {
        return $this->validateRegex(
            $constraint->numbers,
            '/\pN/u',
            $value,
            $constraint->numbers_message,
            Password::NUMBERS_ERROR
        );
    }

    /**
     * Checks the presence of one or more special characters.
     */
    private function checkSpecialChar(Password $constraint, string $value): bool
    {
        return $this->validateRegex(
            $constraint->special_char,
            '/[^p{Ll}\p{Lu}\pL\pN]/u',
            $value,
            $constraint->special_char_message,
            Password::SPECIAL_CHAR_ERROR
        );
    }

    /**
     * Check if the password has been compromised in a data breach.
     */
    private function getPasswordCount(string $password): int
    {
        // hash
        $hash = \strtoupper(\sha1($password));
        $hashPrefix = \substr($hash, 0, 5);

        // load
        $url = \sprintf(self::API_ENDPOINT, $hashPrefix);
        $lines = \file($url, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
        if (false === $lines || [] === $lines) {
            return 0;
        }

        // search
        foreach ($lines as $line) {
            if (!\str_contains($line, ':')) {
                continue;
            }

            [$hashSuffix, $count] = \explode(':', $line);
            if ($hashPrefix . $hashSuffix === $hash) {
                return (int) $count;
            }
        }

        return 0;
    }

    /**
     * @psalm-param non-empty-string $pattern
     */
    private function validateRegex(bool $enabled, string $pattern, string $value, string $message, string $code): bool
    {
        if ($enabled && 1 !== \preg_match($pattern, $value)) {
            return $this->addViolation($message, $value, $code);
        }

        return false;
    }
}
