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
    /**
     * The password field name.
     */
    public const PASSWORD_FIELD = 'password';

    /**
     * The remember field name.
     */
    public const REMEMBER_FIELD = 'remember';

    /**
     * The user field name.
     */
    public const USER_FIELD = 'username';

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field(self::USER_FIELD)
            ->addUserNameType();
        $helper->field(self::PASSWORD_FIELD)
            ->addCurrentPasswordType();
        $helper->field(self::REMEMBER_FIELD)
            ->addCheckboxType();
        parent::addFormFields($helper);
    }

    protected function getLabelPrefix(): ?string
    {
        return 'security.login.';
    }
}
