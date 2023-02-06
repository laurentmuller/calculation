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

use App\Entity\AbstractEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Repeat password type.
 */
class RepeatPasswordType extends AbstractType
{
    /**
     * The default translatable confirm label.
     */
    final public const CONFIRM_LABEL = 'user.password.confirmation';

    /**
     * The default translatable password label.
     */
    final public const PASSWORD_LABEL = 'user.password.label';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'type' => PasswordType::class,
            'invalid_message' => 'password.mismatch',
            'first_options' => self::getPasswordOptions(),
            'second_options' => self::getConfirmOptions(),
            'constraint' => [
                new NotBlank(),
                new Length(min: 6, max: AbstractEntity::MAX_STRING_LENGTH),
            ],
        ]);
    }

    /**
     * Gets the default confirm options (second options).
     */
    public static function getConfirmOptions(): array
    {
        return [
            'label' => self::CONFIRM_LABEL,
            'attr' => [
                'autocomplete' => 'new-password',
                'maxlength' => AbstractEntity::MAX_STRING_LENGTH,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return RepeatedType::class;
    }

    /**
     * Gets the default password options (first options).
     */
    public static function getPasswordOptions(): array
    {
        return [
            'label' => self::PASSWORD_LABEL,
            'hash_property_path' => 'password',
            'attr' => [
                'minlength' => 6,
                'maxlength' => AbstractEntity::MAX_STRING_LENGTH,
                'class' => 'password-strength',
                'autocomplete' => 'new-password',
            ],
        ];
    }
}
