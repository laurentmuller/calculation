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
 * Strength constraint.
 *
 * @author Laurent Muller
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Strength extends Constraint
{
    /**
     * The disable level.
     */
    public const LEVEL_DISABLE = -1;

    /**
     * The maximum level.
     */
    public const LEVEL_MAX = 4;

    /**
     * The minimum level.
     */
    public const LEVEL_MIN = 0;

    /**
     * The password strength (Value from 0 to 4 or -1 to disable).
     */
    public int $minStrength = self::LEVEL_DISABLE;

    /**
     * The password strength error message.
     */
    public string $minStrengthMessage = 'The password is to weak.';
}
