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

use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Service\RoleService;

/**
 * Abstract class to edit permissions rights.
 */
abstract class AbstractRightsType extends AbstractHelperType
{
    public function __construct(protected readonly RoleService $service)
    {
    }

    protected function addRightsType(FormHelper $helper): void
    {
        $helper->field('rights')
            ->add(RightsType::class);
    }

    protected function addRoleType(FormHelper $helper, string $field = 'role'): void
    {
        $helper->field($field)
            ->label('user.fields.role')
            ->updateOption('value_transformer', $this->translateRole(...))
            ->addPlainType();
    }

    #[\Override]
    protected function getLabelPrefix(): string
    {
        return 'user.fields.';
    }

    private function translateRole(string $role): string
    {
        return $this->service->getRoleIconAndName($role);
    }
}
