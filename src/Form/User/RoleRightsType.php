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

namespace App\Form\User;

use App\Form\FormHelper;
use App\Model\Role;

/**
 * Role rights type.
 *
 * @extends AbstractRightsType<Role>
 */
class RoleRightsType extends AbstractRightsType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $this->addRoleType($helper, 'name');
        $this->addRightsType($helper);
    }
}
