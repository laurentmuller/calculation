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
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Strength constraint.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Strength extends Constraint
{
    final public const IS_STRENGTH_ERROR = '6218da5e-12d8-481e-b0fc-9bc4cbaad2ef';

    protected const ERROR_NAMES = [
        self::IS_STRENGTH_ERROR => 'IS_STRENGTH_ERROR',
    ];

    /**
     * The password strength level.
     */
    public StrengthLevel $minimum;

    /**
     * The password strength error message.
     */
    public string $strength_message = 'password.strength_level';

    /**
     * @param string[] $groups
     *
     * @throws ConstraintDefinitionException if the minimum parameter is an integer and cannot be parsed to a strength level
     */
    #[HasNamedArguments]
    public function __construct(
        StrengthLevel|int $minimum = StrengthLevel::NONE,
        public ?string $userNamePath = null,
        public ?string $emailPath = null,
        ?array $options = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options, $groups, $payload);

        if (\is_int($minimum)) {
            $level = StrengthLevel::tryFrom($minimum);
            if (!$level instanceof StrengthLevel) {
                throw new ConstraintDefinitionException(\sprintf('Unable to find a strength level for the value %d.', $minimum));
            }
            $this->minimum = $level;
        } else {
            $this->minimum = $minimum;
        }
    }

    #[\Override]
    public function getDefaultOption(): ?string
    {
        return 'minimum';
    }
}
