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
 * User login type.
 */
class UserLoginType extends AbstractUserCaptchaType
{
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->addUserNameType();
        $helper->field('password')
            ->addCurrentPasswordType();
        $helper->field('remember_me')
            ->addCheckboxType();
        parent::addFormFields($helper);
    }

    protected function getLabelPrefix(): ?string
    {
        return 'security.login.';
    }
}
