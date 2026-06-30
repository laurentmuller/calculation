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
use App\Form\FormHelper;

/**
 * User rights type.
 *
 * @extends AbstractRightsType<User>
 */
class UserRightsType extends AbstractRightsType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->prependIcon('fa-regular fa-user')
            ->addPlainType();
        $helper->field('enabled')
            ->updateOptions([
                'prepend_icon' => 'fa-solid fa-user-check',
                'value_transformer' => $this->translateEnabled(...),
            ])->addPlainType();
        $helper->field('overwrite')
            ->addCheckboxType();
        $this->addRoleType($helper, 'role');
        parent::addFormFields($helper);
    }

    #[\Override]
    protected function getDataClass(): string
    {
        return User::class;
    }
}
