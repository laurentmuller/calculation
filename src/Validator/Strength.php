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
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Strength constraint.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Strength extends Constraint
{
    /**
     * The password strength (Value from 0 to 4 or -1 to disable).
     */
    public int $min_strength;

    /**
     * The password strength error message.
     */
    public string $min_strength_message = 'password.min_strength';

    /**
     * Constructor.
     *
     * @throws InvalidArgumentException if the minimum strength value is not between -1 and 4 (inclusive)
     */
    public function __construct(int $min_strength, public ?string $userNamePath = null, public ?string $emailPath = null)
    {
        if (!\in_array($min_strength, StrengthInterface::ALLOWED_LEVELS, true)) {
            $values = \implode(', ', StrengthInterface::ALLOWED_LEVELS);
            throw new InvalidArgumentException(\sprintf('The minimum strength parameter "%s" for "%s" is invalid. Allowed values: [%s].', $min_strength, static::class, $values));
        }
        $this->min_strength = $min_strength;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): ?string
    {
        return 'min_strength';
    }
}
