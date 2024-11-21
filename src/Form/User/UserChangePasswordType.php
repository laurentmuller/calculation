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
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->updateOption('hidden_input', true)
            ->addPlainType();
        parent::addFormFields($helper);
    }
}
