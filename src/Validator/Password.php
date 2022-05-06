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
use Symfony\Component\Validator\Constraint;

/**
 * Password constraint.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Password extends Constraint
{
    /**
     * Add all violations or stop of the first violation found.
     */
    public bool $all = false;

    /**
     * Checks if the password contains upper and lower characters.
     */
    public bool $case_diff = false;

    /**
     * Case diff error message.
     */
    public string $case_diff_message = 'password.case_diff';

    /**
     * Checks if the password is an e-mail.
     */
    public bool $email = false;

    /**
     * Email error message.
     */
    public string $email_message = 'password.email';

    /**
     * Checks if the password contains letters.
     */
    public bool $letters = true;

    /**
     * Letters error message.
     */
    public string $letters_message = 'password.letters';

    /**
     * Checks the password strength (value from 0 to 4 or -1 to disable).
     */
    public int $min_strength = StrengthInterface::LEVEL_NONE;

    /**
     * Strength error message.
     */
    public string $min_strength_message = 'password.min_strength';

    /**
     * Checks if the password contains numbers.
     */
    public bool $numbers = false;

    /**
     * Numbers error message.
     */
    public string $numbers_message = 'password.numbers';

    /**
     * Checks if the password is compromised.
     */
    public bool $pwned = false;

    /**
     *  Password comprise error message.
     */
    public string $pwned_message = 'password.pwned';

    /**
     * Checks if the password contains special characters.
     */
    public bool $special_char = false;

    /**
     * Special char error message.
     */
    public string $special_char_message = 'password.special_char';
}
