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

namespace App\Tests\Fixture;

use App\Form\Parameters\AbstractHelperParametersType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class FixtureParametersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('parameter', FixtureParameterType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(AbstractHelperParametersType::DEFAULT_VALUES, [])
            ->setAllowedTypes(AbstractHelperParametersType::DEFAULT_VALUES, 'array');
    }
}
