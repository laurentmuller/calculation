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

use App\Form\FormHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Type to reset the user password.
 *
 * @extends AbstractType<\Symfony\Component\Form\FormTypeInterface>
 */
class ResetChangePasswordType extends AbstractType
{
    /**
     * @psalm-param FormBuilderInterface $builder
     *
     * @phpstan-param FormBuilderInterface<mixed> $builder
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $helper = new FormHelper($builder);
        $helper->field('plainPassword')
            ->addRepeatPasswordType('user.password.new', 'user.password.new_confirmation');
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
