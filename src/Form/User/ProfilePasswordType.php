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
 * Change the password of the current (logged) user.
 */
class ProfilePasswordType extends AbstractChangePasswordType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('currentPassword')
            ->addCurrentPasswordType();
        parent::addFormFields($helper);
        $helper->field('username')->addHiddenType();
    }

    #[\Override]
    protected function getLabelPrefix(): ?string
    {
        return null;
    }
}
