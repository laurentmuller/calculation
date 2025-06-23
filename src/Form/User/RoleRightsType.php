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

use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Model\Role;

/**
 * Role rights type.
 */
class RoleRightsType extends AbstractHelperType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('name')
            ->label('user.fields.role')
            ->addPlainType();
        $helper->field('rights')
            ->add(RightsType::class);
    }

    #[\Override]
    protected function getLabelPrefix(): ?string
    {
        return 'user.fields.';
    }
}
