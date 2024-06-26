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
 * Change the user password type.
 *
 * @template-extends AbstractEntityType<User>
 */
class UserChangePasswordType extends AbstractEntityType
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->updateOption('hidden_input', true)
            ->addPlainType();
        $helper->field('plainPassword')
            ->addRepeatPasswordType('user.password.new', 'user.password.new_confirmation');
    }
}
