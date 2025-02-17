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

use App\Parameter\AbstractParameters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template TParameters of AbstractParameters
 *
 * @extends AbstractType<mixed>
 */
abstract class AbstractHelperParametersType extends AbstractType
{
    public const DEFAULT_VALUES = 'default_values';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('display', DisplayParameterType::class);
        $builder->add('homePage', HomePageParameterType::class);
        $builder->add('message', MessageParameterType::class);
        $builder->add('options', OptionsParameterType::class);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->getParametersClass(),
        ]);

        $resolver->setDefault(self::DEFAULT_VALUES, [])
            ->setAllowedTypes(self::DEFAULT_VALUES, 'array');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * @return class-string<TParameters>
     */
    abstract protected function getParametersClass(): string;
}
