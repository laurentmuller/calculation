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
     *
     * @var bool
     */
    public $all = false;

    /**
     * Checks if the password contains upper and lower characters.
     *
     * @var bool
     */
    public $casediff = false;

    /**
     * @var string
     */
    public $casediffMessage = 'The password must be both upper and lower case.';

    /**
     * Checks if the password is an e-mail.
     *
     * @var bool
     */
    public $email = false;

    /**
     * @var string
     */
    public $emailMessage = 'The password cannot be an email address.';

    /**
     * Checks if the password contains letters.
     *
     * @var bool
     */
    public $letters = true;

    /**
     * @var string
     */
    public $lettersMessage = 'The password must contain at least one letter.';

    /**
     * Checks the password strength (Value from 0 to 4 or -1 to disable).
     *
     * @var int
     */
    public $minstrength = -1;

    /**
     * @var string
     */
    public $minstrengthMessage = 'The password is to weak.';

    /**
     * Checks if the password contains numbers.
     *
     * @var bool
     */
    public $numbers = false;

    /**
     * @var string
     */
    public $numbersMessage = 'The password must include at least one digit.';

    /**
     * Checks if the password is compromised.
     *
     * @var bool
     */
    public $pwned = false;

    /**
     * @var string
     */
    public $pwnedMessage = 'The password was found in a compromised password database.';

    /**
     * Checks if the password contains special characters.
     *
     * @var bool
     */
    public $specialchar = false;

    /**
     * @var string
     */
    public $specialcharMessage = 'The password must contain at least one special character.';
}
