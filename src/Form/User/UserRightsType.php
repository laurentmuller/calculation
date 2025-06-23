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
use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Service\RoleService;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * User rights type.
 */
class UserRightsType extends AbstractHelperType
{
    public function __construct(private readonly RoleService $service)
    {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', User::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->addPlainType();
        $helper->field('role')
            ->updateOption('value_transformer', $this->translateRole(...))
            ->addPlainType();
        $helper->field('enabled')
            ->updateOption('value_transformer', $this->translateEnabled(...))
            ->addPlainType();
        $helper->field('overwrite')
            ->rowClass('mt-3')
            ->addCheckboxType();
        $helper->field('rights')
            ->add(RightsType::class);
    }

    #[\Override]
    protected function getLabelPrefix(): ?string
    {
        return 'user.fields.';
    }

    private function translateEnabled(string $value): string
    {
        $enabled = \filter_var($value, \FILTER_VALIDATE_BOOLEAN);

        return $this->service->translateEnabled($enabled);
    }

    private function translateRole(string $role): string
    {
        return $this->service->translateRole($role);
    }
}
