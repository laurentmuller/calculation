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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * User login type.
 */
class UserLoginType extends AbstractUserCaptchaType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field(SecurityAttributes::USER_FIELD)
            ->addUserNameType();
        $helper->field(SecurityAttributes::PASSWORD_FIELD)
            ->addPasswordType();
        $helper->field(SecurityAttributes::REMEMBER_FIELD)
            ->addCheckboxType();
        parent::addFormFields($helper);
    }

    protected function getLabelPrefix(): ?string
    {
        return 'security.login.';
    }
}
