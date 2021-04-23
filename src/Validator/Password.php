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

use Symfony\Component\Validator\Constraint;

/**
 * Password constraint.
 *
 * @author Laurent Muller
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
    public bool $casediff = false;

    /**
     * Case diff error message.
     */
    public string $casediffMessage = 'The password must be both upper and lower case.';

    /**
     * Checks if the password is an e-mail.
     */
    public bool $email = false;

    /**
     * Email error message.
     */
    public string $emailMessage = 'The password cannot be an email address.';

    /**
     * Checks if the password contains letters.
     */
    public bool $letters = true;

    /**
     * Letters error message.
     */
    public string $lettersMessage = 'The password must contain at least one letter.';

    /**
     * Checks the password strength (Value from 0 to 4 or -1 to disable).
     */
    public int $minstrength = -1;

    /**
     * Minimim strength error message.
     */
    public string $minstrengthMessage = 'The password is to weak.';

    /**
     * Checks if the password contains numbers.
     */
    public bool $numbers = false;

    /**
     * Numbers error message.
     */
    public string $numbersMessage = 'The password must include at least one digit.';

    /**
     * Checks if the password is compromised.
     */
    public bool $pwned = false;

    /**
     *  Pawword comprise error message.
     */
    public string $pwnedMessage = 'The password was found in a compromised password database.';

    /**
     * Checks if the password contains special characters.
     */
    public bool $specialchar = false;

    /**
     * Special char error message.
     */
    public string $specialcharMessage = 'The password must contain at least one special character.';
}
