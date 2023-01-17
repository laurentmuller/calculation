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
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::SUBMIT, fn (SubmitEvent $event) => $this->onSubmit($event));
    }

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
            'attr' => [
                'minlength' => 6,
                'maxlength' => AbstractEntity::MAX_STRING_LENGTH,
                'class' => 'password-strength',
                'autocomplete' => 'new-password',
            ],
        ];
    }

    /**
     * Handles the submit event.
     */
    private function onSubmit(SubmitEvent $event): void
    {
        $form = $event->getForm();
        $parent = $form->getParent();

        /** @psalm-var mixed $data */
        $data = $parent?->getData();
        if ($data instanceof User) {
            $plainPassword = (string) $form->getData();
            $encodedPassword = $this->hasher->hashPassword($data, $plainPassword);
            $data->setPassword($encodedPassword);
        }
    }
}
