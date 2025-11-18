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

namespace App\Form\Parameters;

use App\Parameter\UserParameters;

/**
 * @extends AbstractParametersType<UserParameters>
 */
class UserParametersType extends AbstractParametersType
{
    #[\Override]
    protected function getParametersClass(): string
    {
        return UserParameters::class;
    }
}
