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
use App\Security\SecurityAttributes;

/**
 * User login type.
 */
class UserLoginType extends AbstractUserCaptchaType
{
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field(SecurityAttributes::USER_FIELD)
            ->addUserNameType();
        $helper->field(SecurityAttributes::PASSWORD_FIELD)
            ->addCurrentPasswordType();
        $helper->field(SecurityAttributes::REMEMBER_FIELD)
            ->addCheckboxType();
        parent::addFormFields($helper);
    }

    protected function getLabelPrefix(): ?string
    {
        return 'security.login.';
    }
}
