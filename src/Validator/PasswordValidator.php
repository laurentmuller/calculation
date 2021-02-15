<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Validator;

use App\Traits\MathTrait;
use App\Util\FormatUtils;
use Symfony\Component\Validator\Constraint;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZxcvbnPhp\Zxcvbn;

/**
 * Password constraint validator.
 *
 * @author Laurent Muller
 */
class PasswordValidator extends AbstractConstraintValidator
{
    use MathTrait;

    /**
     * The strength levels.
     */
    public const LEVEL_TO_LABEL = [
        0 => 'very_weak',
        1 => 'weak',
        2 => 'medium',
        3 => 'strong',
        4 => 'very_strong',
    ];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct(Password::class);
        $this->translator = $translator;
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
     *
     * @param Password $constraint
     */
    protected function doValidate($value, Constraint $constraint): void
    {
        if ($constraint->all) {
            $this->checkLetters($constraint, $value);
            $this->checkCaseDiff($constraint, $value);
            $this->checkNumber($constraint, $value);
            $this->checkSpecialChar($constraint, $value);
            $this->checkEmail($constraint, $value);
            $this->checkStrength($constraint, $value);
            $this->checkPwned($constraint, $value);
        } else {
            $this->checkLetters($constraint, $value)
                    || $this->checkCaseDiff($constraint, $value)
                    || $this->checkNumber($constraint, $value)
                    || $this->checkSpecialChar($constraint, $value)
                    || $this->checkEmail($constraint, $value)
                    || $this->checkStrength($constraint, $value)
                    || $this->checkPwned($constraint, $value);
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
        $this->context->buildViolation("password.{$message}")
            ->setParameters($parameters)
            ->setInvalidValue($value)
            ->addViolation();

        return true;
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
        if ($constraint->casediff && !\preg_match('/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u', $value)) {
            return $this->addViolation('casediff', $value);
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
        if ($constraint->email && false !== \filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            return $this->addViolation('email', $value);
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
     * Checks the presence of one or more number characters.
     *
     * @param Password $constraint the password constraint
     * @param string   $value      the value to validate
     *
     * @return bool true if a violation is added
     */
    private function checkNumber(Password $constraint, string $value): bool
    {
        if ($constraint->numbers && !\preg_match('/\pN/u', $value)) {
            return $this->addViolation('numbers', $value);
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
                '{{count}}' => FormatUtils::formatInt($count),
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
    private function checkSpecialChar(Password $constraint, string $value): bool
    {
        if ($constraint->specialchar && !\preg_match('/[^p{Ll}\p{Lu}\pL\pN]/u', $value)) {
            return $this->addViolation('specialchar', $value);
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
        if ($constraint->minstrength >= 0) {
            $zx = new Zxcvbn();
            $strength = $zx->passwordStrength($value);
            $score = $strength['score'];
            if ($score < $constraint->minstrength) {
                $strength_min = $this->translateLevel($constraint->minstrength);
                $strength_current = $this->translateLevel($score);
                $parameters = [
                    '{{strength_min}}' => $strength_min,
                    '{{strength_current}}' => $strength_current,
                ];

                return $this->addViolation('minstrength', $value, $parameters);
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
        $url = "https://api.pwnedpasswords.com/range/{$hashPrefix}";
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
        $id = 'password.strength_level.' . self::LEVEL_TO_LABEL[$level];

        return $this->translator->trans($id, []);
    }
}
