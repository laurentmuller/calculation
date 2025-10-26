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

use App\Entity\User;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Type to update the user profile.
 *
 * @extends AbstractEntityType<User>
 */
class ProfileEditType extends AbstractEntityType
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('id')
            ->disabled()
            ->addHiddenType();

        $helper->field('username')
            ->addUserNameType();

        $helper->field('email')
            ->autocomplete('email')
            ->addEmailType();

        $helper->field('currentPassword')
            ->label('user.password.current')
            ->addCurrentPasswordType();

        $helper->field('imageFile')
            ->updateOption('delete_label', 'user.edit.delete_image')
            ->addVichImageType();
    }
}
