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
 * Type to request change password of a user.
 */
class RequestChangePasswordType extends AbstractUserCaptchaType
{
    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('user')
            ->label('resetting.request.user')
            ->widgetClass('user-name')
            ->add(UserNameType::class);

        parent::addFormFields($helper);
    }
}
