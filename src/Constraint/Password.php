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
    /**
     * The allowed option names.
     */
    final public const ALLOWED_OPTIONS = [
        'letter',
        'caseDiff',
        'number',
        'specialChar',
        'email',
    ];

    final public const CASE_DIFF_ERROR = '4c725240-da48-42df-ba9a-ce09a16ab1b5';
    final public const EMAIL_ERROR = '85386dde-1b29-42d4-9b7c-de03693fb963';
    final public const LETTER_ERROR = 'cc369ec9-ea3d-4d27-8f96-6e03bfb63323';
    final public const NUMBER_ERROR = '902a620e-8cf9-42bd-9219-3938c3fea0c5';
    final public const SPECIAL_CHAR_ERROR = '5c5998ca-d67b-45ed-b210-dda950c8ea09';

    protected const ERROR_NAMES = [
        self::LETTER_ERROR => 'LETTER_ERROR',
        self::CASE_DIFF_ERROR => 'CASE_DIFF_ERROR',
        self::NUMBER_ERROR => 'NUMBER_ERROR',
        self::SPECIAL_CHAR_ERROR => 'SPECIAL_CHAR_ERROR',
        self::EMAIL_ERROR => 'EMAIL_ERROR',
    ];

    /**
     * Test all violations (true) or stop after the first violation found (false).
     */
    public bool $all = false;

    /**
     * Checks if the password contains upper and lower characters.
     */
    public bool $caseDiff = false;

    /**
     * Case diff error message.
     */
    public string $caseDiffMessage = 'password.caseDiff';

    /**
     * Checks if the password is an e-mail.
     */
    public bool $email = false;

    /**
     * Email error message.
     */
    public string $emailMessage = 'password.email';

    /**
     * Checks if the password contains letters.
     */
    public bool $letter = false;

    /**
     * Letters error message.
     */
    public string $letterMessage = 'password.letter';

    /**
     * Checks if the password contains numbers.
     */
    public bool $number = false;

    /**
     * Numbers error message.
     */
    public string $numberMessage = 'password.number';

    /**
     * Checks if the password contains special characters.
     */
    public bool $specialChar = false;

    /**
     * Special char error message.
     */
    public string $specialCharMessage = 'password.specialChar';

    #[HasNamedArguments]
    public function __construct(
        ?bool $all = null,
        ?bool $letter = null,
        ?bool $caseDiff = null,
        ?bool $number = null,
        ?bool $specialChar = null,
        ?bool $email = null,
        ?array $options = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options, $groups, $payload);

        $this->all = $all ?? $this->all;
        $this->letter = $letter ?? $this->letter;
        $this->caseDiff = $caseDiff ?? $this->caseDiff;
        $this->number = $number ?? $this->number;
        $this->specialChar = $specialChar ?? $this->specialChar;
        $this->email = $email ?? $this->email;
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
