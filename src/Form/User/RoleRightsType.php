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

/**
 * Role rights type.
 */
class RoleRightsType extends RightsType
{
    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        parent::addFormFields($helper);

        $helper->field('name')
            ->label('user.fields.role')
            ->addPlainType(true);
    }
}
