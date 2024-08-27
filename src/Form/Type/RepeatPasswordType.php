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

use App\Interfaces\EntityInterface;
use App\Interfaces\UserInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Repeat a password type.
 *
 * @extends AbstractType<PasswordType>
 */
class RepeatPasswordType extends AbstractType
{
    /**
     * The default translatable confirmation label.
     */
    final public const CONFIRM_LABEL = 'user.password.confirmation';

    /**
     * The default translatable password label.
     */
    final public const PASSWORD_LABEL = 'user.password.label';

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
                new Length(min: UserInterface::MIN_PASSWORD_LENGTH, max: EntityInterface::MAX_STRING_LENGTH),
            ],
        ]);
    }

    /**
     * Gets the default confirmation options (second options).
     */
    public static function getConfirmOptions(): array
    {
        return [
            'label' => self::CONFIRM_LABEL,
            'attr' => [
                'autocomplete' => 'new-password',
                'maxlength' => EntityInterface::MAX_STRING_LENGTH,
            ],
        ];
    }

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
                'minlength' => UserInterface::MIN_PASSWORD_LENGTH,
                'maxlength' => EntityInterface::MAX_STRING_LENGTH,
                'class' => 'password-strength',
                'autocomplete' => 'new-password',
            ],
        ];
    }
}
