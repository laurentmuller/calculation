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
     * Add all violation or stop of the first found.
     *
     * @var bool
     */
    public $allViolations = false;

    /**
     * Checks if the password is within a blacklist passwords.
     *
     * @var bool
     */
    public $blackList = false;
    public $blackListMessage = 'The password is part of the forbidden words list.';

    /**
     * Checks if the password contains upper and lower characters.
     *
     * @var bool
     */
    public $caseDiff = false;
    public $caseDiffMessage = 'The password must be both upper and lower case.';

    /**
     * Checks if the password is an e-mail.
     *
     * @var bool
     */
    public $email = false;
    public $emailMessage = 'The password cannot be an email address.';

    /**
     * Checks if the password contains letters.
     *
     * @var bool
     */
    public $letters = true;
    public $lettersMessage = 'The password must contain at least one letter.';

    /**
     * Checks the minimum length of the password.
     *
     * @var int
     */
    public $minLength = 6;
    public $minLengthMessage = 'The password must be at least {{length}} characters long.';

    /**
     * Checks the password strength (Value from 0 to 4 or -1 to disable).
     *
     * @var int
     */
    public $minStrength = -1;
    public $minStrengthMessage = 'The password is to weak.';

    /**
     * Checks if the password contains numbers.
     *
     * @var bool
     */
    public $numbers = false;
    public $numbersMessage = 'The password must include at least one digit.';

    /**
     * Checks if the password is compromised.
     *
     * @var bool
     */
    public $pwned = false;
    public $pwnedMessage = 'The password was found in a compromised password database.';

    /**
     * Checks if the password contains special characters.
     *
     * @var bool
     */
    public $specialChar = false;
    public $specialCharMessage = 'The password must contain at least one special character.';
}
