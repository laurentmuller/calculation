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
 * Type to reset the user password.
 */
class ResetChangePasswordType extends AbstractUserCaptchaType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('plainPassword')
            ->addRepeatPasswordType('user.password.new', 'user.password.new_confirmation');
        parent::addFormFields($helper);
    }
}
