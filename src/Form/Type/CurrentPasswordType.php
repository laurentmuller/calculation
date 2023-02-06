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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Type for the current user password.
 */
class CurrentPasswordType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'mapped' => false,
            'label' => 'user.password.current',
            'autocomplete' => 'current-password',
            'attr' => [
                'minLength' => 6,
                'maxLength' => AbstractEntity::MAX_STRING_LENGTH,
            ],
            'constraints' => [
                new NotBlank(),
                new Length(min: 6, max: AbstractEntity::MAX_STRING_LENGTH),
                new UserPassword(['message' => 'current_password.invalid']),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return PasswordType::class;
    }
}
