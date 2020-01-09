<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Validator;

use App\Service\BlacklistProvider;
use App\Traits\MathTrait;
use App\Traits\NumberFormatterTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZxcvbnPhp\Zxcvbn;

/**
 * Password validator.
 *
 * @author Laurent Muller
 */
class PasswordValidator extends ConstraintValidator
{
    use MathTrait;
    use NumberFormatterTrait;

    /**
     * The strength level.
     *
     * @var array
     */
    public static $LEVEL_TO_LABEL = [
        0 => 'very_weak',
        1 => 'weak',
        2 => 'medium',
        3 => 'strong',
        4 => 'very_strong',
    ];

    /**
     * @var BlacklistProvider
     */
    private $provider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, BlacklistProvider $provider)
    {
        $this->translator = $translator;
        $this->provider = $provider;
    }

    /**
     * Gets the translator.
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        // check constraint type
        if (!$constraint instanceof Password) {
            throw new UnexpectedTypeException($constraint, Password::class);
        }

        // value?
        if (null === $value || '' === $value) {
            return;
        }

        // check value type
        if (!\is_scalar($value) && !(\is_object($value) && \method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        // convert
        $value = (string) $value;

        // validate
        if ($constraint->allViolations) {
            $this->checkMinLength($constraint, $value);
            $this->checkLetters($constraint, $value);
            $this->checkCaseDiff($constraint, $value);
            $this->checkNumber($constraint, $value);
            $this->checkSpecialCharacter($constraint, $value);
            $this->checkEmail($constraint, $value);
            $this->checkStrength($constraint, $value);
            $this->checkBlackList($constraint, $value);
            $this->checkPwned($constraint, $value);
        } else {
            $this->checkMinLength($constraint, $value)
                || $this->checkLetters($constraint, $value)
                || $this->checkCaseDiff($constraint, $value)
                || $this->checkNumber($constraint, $value)
                || $this->checkSpecialCharacter($constraint, $value)
                || $this->checkEmail($constraint, $value)
                || $this->checkStrength($constraint, $value)
                || $this->checkBlackList($constraint, $value)
                || $this->checkPwned($constraint, $value);
        }
    }

    /**
     * Adds a violation.
     *
     * @param string $message    the message
     * @param string $value      the value
     * @param array  $parameters the parameters
     *
     * @return bool this function return always true
     */
    private function addViolation(string $message, string $value, array $parameters = []): bool
    {
        $this->context->buildViolation("password.{$message}")
            ->setParameters($parameters)
            ->setInvalidValue($value)
            ->addViolation();

        return true;
    }

    /**
     * Checks if the value is within the passwords blacklist.
     *
     * @param Password $constraint the password constraint
     * @param string   $value      the value to validate
     *
     * @return bool true if a violation is added
     */
    private function checkBlackList(Password $constraint, string $value): bool
    {
        if ($constraint->blackList && $this->provider && true === $this->provider->isBlacklisted($value)) {
            return $this->addViolation('blacklist', $value);
        }

        return false;
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
        if ($constraint->caseDiff && !\preg_match('/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u', $value)) {
            return $this->addViolation('caseDiff', $value);
        }

        return false;
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
        if ($constraint->email && false !== \filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return  $this->addViolation('email', $value);
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
        if ($constraint->letters && !\preg_match('/\pL/u', $value)) {
            return $this->addViolation('letters', $value);
        }

        return false;
    }

    /**
     * Checks the minimum length.
     *
     * @param Password $constraint the password constraint
     * @param string   $value      the value to validate
     *
     * @return bool true if a violation is added
     */
    private function checkMinLength(Password $constraint, string $value): bool
    {
        if ($constraint->minLength > 0 && (\mb_strlen($value) < $constraint->minLength)) {
            $parameters = [
                '{{length}}' => $constraint->minLength,
            ];

            return $this->addViolation('minLength', $value, $parameters);
        }

        return false;
    }

    /**
     * Checks the presence of on or more number characters.
     *
     * @param Password $constraint the password constraint
     * @param string   $value      the value to validate
     *
     * @return bool true if a violation is added
     */
    private function checkNumber(Password $constraint, string $value): bool
    {
        if ($constraint->numbers && !\preg_match('/\pN/u', $value)) {
            return  $this->addViolation('numbers', $value);
        }

        return false;
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
                '{{count}}' => $this->localeInt($count),
            ];

            return $this->addViolation('pwned', $value, $parameters);
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
    private function checkSpecialCharacter(Password $constraint, string $value): bool
    {
        if ($constraint->specialCharacter && !\preg_match('/[^p{Ll}\p{Lu}\pL\pN]/u', $value)) {
            return $this->addViolation('specialCharacter', $value);
        }

        return false;
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
        if ($constraint->minStrength >= 0) {
            $zx = new Zxcvbn();
            $strength = $zx->passwordStrength($value);
            $score = $strength['score'];
            if ($score < $constraint->minStrength) {
                $strength_min = $this->translateLevel($constraint->minStrength);
                $strength_current = $this->translateLevel($score);
                $parameters = [
                    '{{strength_min}}' => $strength_min,
                    '{{strength_current}}' => $strength_current,
                ];

                return $this->addViolation('minStrength', $value, $parameters);
            }
        }

        return false;
    }

    /**
     * Check if you have an account that has been compromised in a data breach.
     *
     * @param string $password the password to verify
     *
     * @return int the number of compromised
     */
    private function getPasswordCount(string $password): int
    {
        // hash
        $hash = \strtoupper(\sha1($password));
        $hashPrefix = \substr($hash, 0, 5);

        // load
        $url = "https://api.pwnedpasswords.com/range/{$hashPrefix}";
        $lines = \file($url, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return 0;
        }

        // search
        foreach ($lines as $line) {
            list($hashSuffix, $count) = \explode(':', $line);
            if ($hashPrefix . $hashSuffix === $hash) {
                return (int) $count;
            }
        }

        return 0;
    }

    /**
     * Translate the level.
     *
     * @param int $level the level (0 - 4)
     *
     * @return string the translated level
     */
    private function translateLevel(int $level): string
    {
        $level = $this->validateIntRange($level, 0, 4);
        $id = 'password.strength_level.' . self::$LEVEL_TO_LABEL[$level];

        return $this->translator->trans($id, []);
    }
}
