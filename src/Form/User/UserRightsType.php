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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * User rights type.
 *
 * @extends AbstractRightsType<User>
 */
class UserRightsType extends AbstractRightsType
{
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
        $helper->field('enabled')
            ->updateOption('value_transformer', $this->translateEnabled(...))
            ->addPlainType();
        $helper->field('overwrite')
            ->addCheckboxType();
        $this->addRoleType($helper);
        $this->addRightsType($helper);
    }

    private function translateEnabled(string $value): string
    {
        $enabled = \filter_var($value, \FILTER_VALIDATE_BOOLEAN);

        return $this->service->translateEnabled($enabled);
    }
}
