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
use App\Traits\RoleTranslatorTrait;
use App\Traits\TranslatorAwareTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * User rights type.
 */
class UserRightsType extends RightsType implements ServiceSubscriberInterface
{
    use RoleTranslatorTrait;
    use ServiceMethodsSubscriberTrait;
    use TranslatorAwareTrait;

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('data_class', User::class);
    }

    protected function addFormFields(FormHelper $helper): void
    {
        parent::addFormFields($helper);
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
    }

    protected function getLabelPrefix(): ?string
    {
        return 'user.fields.';
    }

    private function translateEnabled(string $enabled): string
    {
        $enabled = \filter_var($enabled, \FILTER_VALIDATE_BOOLEAN);

        return $this->trans($enabled ? 'common.value_enabled' : 'common.value_disabled');
    }
}
