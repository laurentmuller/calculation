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
 * Type to change the profile of the current (logged) user.
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
                new Callback(function (User $_user, ExecutionContextInterface $context): void {
                    $this->validate($context);
                }),
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
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        // current password
        $helper->field('current_password')
            ->label('user.password.current')
            ->constraints(new NotBlank(), new UserPassword(['message' => 'current_password.invalid']))
            ->notMapped()
            ->autocomplete('current-password')
            ->add(PasswordType::class);

        // new password
        $helper->field('plainPassword')
            ->notMapped()
            ->addRepeatPasswordType('user.password.new', 'user.password.new_confirmation');

        // check password
        $helper->field('checkPassword')
            ->label('user.change_password.check_password')
            ->notRequired()
            ->notMapped()
            ->addCheckboxType();

        // username for ajax validation
        $helper->field('username')->addHiddenType();
    }

    /**
     * Conditional validation depending on the check password checkbox.
     */
    private function validate(ExecutionContextInterface $context): void
    {
        /** @var Form $root */
        $root = $context->getRoot();

        // not checked so continue.
        /** @var bool|mixed $checkPassword */
        $checkPassword = $root->get('checkPassword')->getData();
        if (\is_bool($checkPassword) && !$checkPassword) {
            return;
        }

        // check password
        /** @var Form $password */
        $password = $root->get('plainPassword');
        $violations = $context->getValidator()->validate($password->getData(), [
            new NotCompromisedPassword(),
        ]);

        // if compromised assign the error to the password field
        if ($violations instanceof ConstraintViolationList && $violations->count() > 0) {
            $password->addError(new FormError((string) $violations));
        }
    }
}
