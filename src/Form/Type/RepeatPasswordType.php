<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Repeat password type.
 *
 * @author Laurent Muller
 */
class RepeatPasswordType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type' => PasswordType::class,
            'options' => [
                'translation_domain' => 'FOSUserBundle',
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ],
            'first_options' => self::getFirstOptions(),
            'second_options' => self::getSecondOptions(),
            'invalid_message' => 'fos_user.password.mismatch',
        ]);
    }

    /**
     * Gets the default first options.
     */
    public static function getFirstOptions(): array
    {
        return [
            'label' => 'form.password',
            'attr' => [
                'minlength' => 6,
                'maxlength' => 255,
                'class' => 'password-strength',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return RepeatedType::class;
    }

    /**
     * Gets the default second options.
     */
    public static function getSecondOptions(): array
    {
        return [
            'label' => 'form.password_confirmation',
        ];
    }
}
