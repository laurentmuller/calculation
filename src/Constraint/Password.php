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

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidOptionsException;

/**
 * Password constraint.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Password extends Constraint
{
    /** The allowed option names. */
    public const array ALLOWED_OPTIONS = [
        'letter',
        'caseDiff',
        'number',
        'specialChar',
        'email',
    ];

    public const string CASE_DIFF_ERROR = '4c725240-da48-42df-ba9a-ce09a16ab1b5';
    public const string EMAIL_ERROR = '85386dde-1b29-42d4-9b7c-de03693fb963';
    public const string LETTER_ERROR = 'cc369ec9-ea3d-4d27-8f96-6e03bfb63323';
    public const string NUMBER_ERROR = '902a620e-8cf9-42bd-9219-3938c3fea0c5';
    public const string SPECIAL_CHAR_ERROR = '5c5998ca-d67b-45ed-b210-dda950c8ea09';

    protected const array ERROR_NAMES = [
        self::LETTER_ERROR => 'LETTER_ERROR',
        self::CASE_DIFF_ERROR => 'CASE_DIFF_ERROR',
        self::NUMBER_ERROR => 'NUMBER_ERROR',
        self::SPECIAL_CHAR_ERROR => 'SPECIAL_CHAR_ERROR',
        self::EMAIL_ERROR => 'EMAIL_ERROR',
    ];

    /** Case diff error message. */
    public string $caseDiffMessage = 'password.caseDiff';

    /** Email error message. */
    public string $emailMessage = 'password.email';

    /** Letters error message. */
    public string $letterMessage = 'password.letter';

    /** Numbers error message. */
    public string $numberMessage = 'password.number';

    /** Special char error message. */
    public string $specialCharMessage = 'password.specialChar';

    /**
     * @param bool $all         test all violations (true) or stop after the first violation found (false)
     * @param bool $letter      checks if the password contains letters
     * @param bool $caseDiff    checks if the password contains upper and lower characters
     * @param bool $number      checks if the password contains numbers
     * @param bool $specialChar checks if the password contains special characters
     * @param bool $email       checks if the password is an e-mail
     */
    #[HasNamedArguments]
    public function __construct(
        public bool $all = false,
        public bool $letter = false,
        public bool $caseDiff = false,
        public bool $number = false,
        public bool $specialChar = false,
        public bool $email = false,
        ?array $options = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options, $groups, $payload);
    }

    /**
     * Gets an option value.
     *
     * @throws InvalidOptionsException If an invalid option name is given
     */
    public function isOption(string $option): bool
    {
        return match ($option) {
            'all' => $this->all,
            'letter' => $this->letter,
            'number' => $this->number,
            'caseDiff' => $this->caseDiff,
            'specialChar' => $this->specialChar,
            'email' => $this->email,
            default => throw new InvalidOptionsException(\sprintf('The option "%s" does not exist.', $option), [$option])
        };
    }

    /**
     * Sets an option value.
     *
     * @throws InvalidOptionsException If an invalid option name is given
     */
    public function setOption(string $option, bool $value): self
    {
        match ($option) {
            'all' => $this->all = $value,
            'letter' => $this->letter = $value,
            'number' => $this->number = $value,
            'caseDiff' => $this->caseDiff = $value,
            'specialChar' => $this->specialChar = $value,
            'email' => $this->email = $value,
            default => throw new InvalidOptionsException(\sprintf('The option "%s" does not exist.', $option), [$option])
        };

        return $this;
    }
}
