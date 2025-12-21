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
 * Change the user password type.
 */
class UserChangePasswordType extends AbstractChangePasswordType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->updateOptions([
                'prepend_icon' => 'fa-regular fa-user',
                'hidden_input' => true,
            ])
            ->label('user.fields.username_full')
            ->addPlainType();
        parent::addFormFields($helper);
    }
}
