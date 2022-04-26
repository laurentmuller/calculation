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
    public int $minstrength;

    /**
     * The password strength error message.
     */
    public string $minstrengthMessage = 'password.minstrength';

    /**
     * Constructor.
     *
     * @throws InvalidArgumentException if the minstrength value is not between -1 and 4 (inclusive)
     */
    public function __construct(int $minstrength, public ?string $userNamePath = null, public ?string $emailPath = null)
    {
        if (!\in_array($minstrength, StrengthInterface::ALLOWED_LEVELS, true)) {
            $values = \implode(', ', StrengthInterface::ALLOWED_LEVELS);
            throw new InvalidArgumentException(\sprintf('The minstrength parameter "%s" for "%s" is invalid. Allowed values: [%s].', $minstrength, static::class, $values));
        }
        $this->minstrength = $minstrength;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): string
    {
        return 'minstrength';
    }
}
