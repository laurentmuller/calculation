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

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Repeat password type.
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
            'first_options' => self::getFirstOptions(),
            'second_options' => self::getSecondOptions(),
            'invalid_message' => 'password.mismatch',
        ]);
    }

    /**
     * Gets the default first options.
     */
    public static function getFirstOptions(): array
    {
        return [
            'label' => 'user.password.label',
            'attr' => [
                'minlength' => 6,
                'maxlength' => 255,
                'class' => 'password-strength',
                'autocomplete' => 'new-password',
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
            'label' => 'user.password.confirmation',
            'attr' => [
                'autocomplete' => 'new-password',
            ],
        ];
    }
}
