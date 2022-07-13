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

use App\Interfaces\StrengthInterface;
use App\Traits\StrengthTranslatorTrait;
use App\Util\FormatUtils;
use Symfony\Component\Validator\Constraint;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZxcvbnPhp\Zxcvbn;

/**
 * Password constraint validator.
 *
 * @extends AbstractConstraintValidator<Password>
 */
class PasswordValidator extends AbstractConstraintValidator
{
    use StrengthTranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
        parent::__construct(Password::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * {@inheritdoc}
     *
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
     *
     * @param string $message    the message
     * @param string $value      the value
     * @param array  $parameters an optional array with the parameter names as keys and
     *                           the values to be inserted in their place as values
     *
     * @return bool this function return always true
     */
    private function addViolation(string $message, string $value, array $parameters = []): bool
    {
        $this->context->buildViolation($message)
            ->setParameters($parameters)
            ->setInvalidValue($value)
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
        $this->checkStrength($constraint, $value);
        $this->checkPwned($constraint, $value);
    }

    /**
     * Check util a violation is added.
     */
    private function checkAny(string $value, Password $constraint): bool
    {
        return $this->checkLetters($constraint, $value)
            || $this->checkCaseDiff($constraint, $value)
            || $this->checkNumber($constraint, $value)
            || $this->checkSpecialChar($constraint, $value)
            || $this->checkEmail($constraint, $value)
            || $this->checkStrength($constraint, $value)
            || $this->checkPwned($constraint, $value);
    }

    /**
     * Checks the presence of lower/upper character.
     *
     * @param Password $constraint the password constraint
     * @param string   $value      the value to validate
     *
     * @return bool true if a violation is added
     */
    private function checkCaseDiff(Password $constraint, string $value): bool
    {
        return $this->validateRegex($constraint->case_diff, '/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u', $value, $constraint->case_diff_message);
    }

    /**
     * Checks if the value is an e-mail.
     *
     * @param Password $constraint the password constraint
     * @param string   $value      the value to validate
     *
     * @return bool true if a violation is added
     */
    private function checkEmail(Password $constraint, string $value): bool
    {
        if ($constraint->email && false !== \filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            return $this->addViolation($constraint->email_message, $value);
        }

        return false;
    }

    /**
     * Checks the presence of letter character.
     *
     * @param Password $constraint the password constraint
     * @param string   $value      the value to validate
     *
     * @return bool true if a violation is added
     */
    private function checkLetters(Password $constraint, string $value): bool
    {
        return $this->validateRegex($constraint->letters, '/\pL/u', $value, $constraint->letters_message);
    }

    /**
     * Checks the presence of one or more numbers characters.
     *
     * @param Password $constraint the password constraint
     * @param string   $value      the value to validate
     *
     * @return bool true if a violation is added
     */
    private function checkNumber(Password $constraint, string $value): bool
    {
        return $this->validateRegex($constraint->numbers, '/\pN/u', $value, $constraint->numbers_message);
    }

    /**
     * Check if the password is compromised.
     *
     * @param Password $constraint the password constraint
     * @param string   $value      the value to validate
     *
     * @return bool true if a violation is added
     */
    private function checkPwned(Password $constraint, string $value): bool
    {
        if ($constraint->pwned && $count = $this->getPasswordCount($value)) {
            $parameters = [
                '{{count}}' => FormatUtils::formatInt($count),
            ];

            return $this->addViolation($constraint->pwned_message, $value, $parameters);
        }

        return false;
    }

    /**
     * Checks the presence of one or more special characters.
     *
     * @param Password $constraint the password constraint
     * @param string   $value      the value to validate
     *
     * @return bool true if a violation is added
     */
    private function checkSpecialChar(Password $constraint, string $value): bool
    {
        return $this->validateRegex($constraint->special_char, '/[^p{Ll}\p{Lu}\pL\pN]/u', $value, $constraint->special_char_message);
    }

    /**
     * Checks the password strength.
     *
     * @param Password $constraint the password constraint
     * @param string   $value      the value to validate
     *
     * @return bool true if a violation is added
     */
    private function checkStrength(Password $constraint, string $value): bool
    {
        if (StrengthInterface::LEVEL_NONE !== $constraint->min_strength) {
            $service = new Zxcvbn();
            $result = $service->passwordStrength($value);
            $score = (int) $result['score'];
            $minimum = $constraint->min_strength;
            if ($score < $minimum) {
                $this->addStrengthViolation($this->context, $minimum, $score);

                return true;
            }
        }

        return false;
    }

    /**
     * Check if the password has been compromised in a data breach.
     *
     * @param string $password the password to verify
     *
     * @return int the number of compromised passwords
     */
    private function getPasswordCount(string $password): int
    {
        // hash
        $hash = \strtoupper(\sha1($password));
        $hashPrefix = \substr($hash, 0, 5);

        // load
        $url = \sprintf('https://api.pwnedpasswords.com/range/%s', $hashPrefix);
        $lines = \file($url, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return 0;
        }

        // search
        foreach ($lines as $line) {
            [$hashSuffix, $count] = \explode(':', $line);
            if ($hashPrefix . $hashSuffix === $hash) {
                return (int) $count;
            }
        }

        return 0;
    }

    private function validateRegex(bool $apply, string $pattern, string $value, string $message): bool
    {
        if ($apply && !\preg_match($pattern, $value)) {
            return $this->addViolation($message, $value);
        }

        return false;
    }
}
