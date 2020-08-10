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
 * A Enabled/Disabled (translated) choice type.
 *
 * @author Laurent Muller
 */
class EnabledDisabledType extends AbstractChoiceType
{
    /**
     * {@inheritdoc}
     */
    protected function getChoices(): array
    {
        return [
            'common.value_enabled' => true,
            'common.value_disabled' => false,
        ];
    }
}
