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

namespace App\Form\Type;

use App\Form\AbstractChoiceType;

/**
 * A form type to select a minimum password strength.
 *
 * @author Laurent Muller
 */
class MinStrengthType extends AbstractChoiceType
{
    /**
     * {@inheritdoc}
     */
    protected function getChoices(): array
    {
        return [
            'password.strength_level.none' => -1,
            'password.strength_level.very_weak' => 0,
            'password.strength_level.weak' => 1,
            'password.strength_level.medium' => 2,
            'password.strength_level.very_strong' => 3,
        ];
    }
}
