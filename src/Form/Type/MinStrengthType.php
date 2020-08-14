<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
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
