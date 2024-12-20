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

namespace App\Form\Parameters;

use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Parameter\UserParameters;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserParametersType extends AbstractHelperType
{
    public const DEFAULT_VALUES = 'default_values';

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserParameters::class,
        ]);

        $resolver->setDefault(self::DEFAULT_VALUES, [])
            ->setAllowedTypes(self::DEFAULT_VALUES, 'array');
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $this->addParameterType($helper, 'display', DisplayParameterType::class);
        $this->addParameterType($helper, 'homePage', HomePageParameterType::class);
        $this->addParameterType($helper, 'message', MessageParameterType::class);
        $this->addParameterType($helper, 'options', OptionsParameterType::class);
    }

    /**
     * @psalm-template T of AbstractParameterType
     *
     * @psalm-param class-string<T> $class
     */
    private function addParameterType(FormHelper $helper, string $name, string $class): void
    {
        $helper->field($name)
            ->label(false)
            ->add($class);
    }
}
