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

namespace App\Form\Type;

use App\Form\AbstractChoiceType;
use App\Interfaces\StrengthInterface;

/**
 * A form type to select a minimum password strength.
 *
 * @author Laurent Muller
 */
class MinStrengthType extends AbstractChoiceType
{
    /**
     * The map between level values and translatable texts.
     */
    final public const CHOICE_LEVELS = [
        StrengthInterface::LEVEL_NONE => 'password.strength_level.none',
        StrengthInterface::LEVEL_VERY_WEEK => 'password.strength_level.very_weak',
        StrengthInterface::LEVEL_WEEK => 'password.strength_level.weak',
        StrengthInterface::LEVEL_MEDIUM => 'password.strength_level.medium',
        StrengthInterface::LEVEL_STRONG => 'password.strength_level.strong',
        StrengthInterface::LEVEL_VERY_STRONG => 'password.strength_level.very_strong',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getChoices(): array
    {
        return \array_flip(self::CHOICE_LEVELS);
    }
}
