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

use App\Enums\StrengthLevel;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Strength constraint.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Strength extends Constraint
{
    public const string STRENGTH_ERROR = '6218da5e-12d8-481e-b0fc-9bc4cbaad2ef';

    protected const array ERROR_NAMES = [
        self::STRENGTH_ERROR => 'STRENGTH_ERROR',
    ];

    /**
     * The password strength error message.
     */
    public string $message = 'password.strength_level';

    /**
     * @param StrengthLevel $minimum      the password strength level
     * @param string|null   $userNamePath the username property path
     * @param string|null   $emailPath    the email property path
     */
    #[HasNamedArguments]
    public function __construct(
        public StrengthLevel $minimum = StrengthLevel::NONE,
        public ?string $userNamePath = null,
        public ?string $emailPath = null,
        ?array $options = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options, $groups, $payload);
    }
}
