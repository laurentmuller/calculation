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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidOptionsException;

/**
 * Password constraint.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Password extends Constraint
{
    final public const CASE_DIFF_ERROR = '4c725240-da48-42df-ba9a-ce09a16ab1b5';

    final public const EMAIL_ERROR = '85386dde-1b29-42d4-9b7c-de03693fb963';

    final public const LETTERS_ERROR = 'cc369ec9-ea3d-4d27-8f96-6e03bfb63323';

    final public const NUMBERS_ERROR = '902a620e-8cf9-42bd-9219-3938c3fea0c5';

    final public const SPECIAL_CHAR_ERROR = '5c5998ca-d67b-45ed-b210-dda950c8ea09';

    protected const ERROR_NAMES = [
        self::LETTERS_ERROR => 'LETTERS_ERROR',
        self::CASE_DIFF_ERROR => 'CASE_DIFF_ERROR',
        self::NUMBERS_ERROR => 'NUMBERS_ERROR',
        self::SPECIAL_CHAR_ERROR => 'SPECIAL_CHAR_ERROR',
        self::EMAIL_ERROR => 'EMAIL_ERROR',
    ];

    /**
     * Test all violations (true) or stop when the first violation is found (false).
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
     * Checks if the password contains numbers.
     */
    public bool $numbers = false;

    /**
     * Numbers error message.
     */
    public string $numbers_message = 'password.numbers';

    /**
     * Checks if the password contains special characters.
     */
    public bool $special_char = false;

    /**
     * Special char error message.
     */
    public string $special_char_message = 'password.special_char';

    /**
     * @param string[] $groups
     */
    public function __construct(
        ?bool $all = null,
        ?bool $letters = null,
        ?bool $case_diff = null,
        ?bool $numbers = null,
        ?bool $special_char = null,
        ?bool $email = null,
        ?array $options = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options, $groups, $payload);

        $this->all = $all ?? $this->all;
        $this->letters = $letters ?? $this->letters;
        $this->case_diff = $case_diff ?? $this->case_diff;
        $this->numbers = $numbers ?? $this->numbers;
        $this->special_char = $special_char ?? $this->special_char;
        $this->email = $email ?? $this->email;
    }

    /**
     * @throws InvalidOptionsException If an invalid option name is given
     */
    public function __get(string $option): mixed
    {
        return match ($option) {
            'all' => $this->all,
            'letters' => $this->letters,
            'case_diff' => $this->case_diff,
            'numbers' => $this->numbers,
            'special_char' => $this->special_char,
            'email' => $this->email,
            default => parent::__get($option),
        };
    }

    /**
     * @psalm-param bool|array $value
     *
     * @throws InvalidOptionsException If an invalid option name is given
     */
    public function __set(string $option, mixed $value): void
    {
        match ($option) {
            'all' => $this->all = (bool) $value,
            'letters' => $this->letters = (bool) $value,
            'case_diff' => $this->case_diff = (bool) $value,
            'numbers' => $this->numbers = (bool) $value,
            'special_char' => $this->special_char = (bool) $value,
            'email' => $this->email = (bool) $value,
            default => parent::__set($option, $value),
        };
    }
}
