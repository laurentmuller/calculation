<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\User;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Type to change the proilfe of the current (logged) user.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityType<User>
 */
class ProfileChangePasswordType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(User::class);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'constraints' => [
                new Callback([$this, 'validate']),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * Conditional validation depending on the check password checkbox.
     */
    public function validate(User $user, ExecutionContextInterface $context): void
    {
        /** @var Form $root */
        $root = $context->getRoot();

        // not checked so continue.
        $checkPassword = $root->get('checkPassword')->getData();
        if (\is_bool($checkPassword) && !$checkPassword) {
            return;
        }

        // check password
        $password = $context->getRoot()->get('plainPassword');
        $violations = $context->getValidator()->validate($password->getData(), [
            new NotCompromisedPassword(),
        ]);

        // if compromised assign the error to the password field
        if ($violations instanceof ConstraintViolationList && $violations->count() > 0) {
            if ($password instanceof Form) {
                $password->addError(new FormError((string) $violations));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        // current password
        $helper->field('current_password')
            ->label('user.password.current')
            ->updateOption('constraints', [
                new NotBlank(),
                new UserPassword(['message' => 'current_password.invalid']),
            ])
            ->notMapped()
            ->autocomplete('current-password')
            ->add(PasswordType::class);

        // new password
        $helper->field('plainPassword')
            ->addRepeatPasswordType('user.password.new', 'user.password.new_confirmation');

        // username for ajax validation
        $helper->field('username')->addHiddenType();

        // check password
        $helper->field('checkPassword')
            ->label('user.change_password.check_password')
            ->notRequired()
            ->notMapped()
            ->addCheckboxType();
    }
}
