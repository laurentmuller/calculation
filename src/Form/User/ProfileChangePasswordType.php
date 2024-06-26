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
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Type to change the password of the current (logged) user.
 *
 * @template-extends AbstractEntityType<User>
 */
class ProfileChangePasswordType extends AbstractEntityType
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'constraints' => [
                new Callback(function (User $user, ExecutionContextInterface $context): void {
                    $this->validate($context);
                }),
            ],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('currentPassword')
            ->addCurrentPasswordType();

        $helper->field('plainPassword')
            ->addRepeatPasswordType('user.password.new', 'user.password.new_confirmation');

        $helper->field('checkPassword')
            ->label('user.change_password.check_password')
            ->notMapped()
            ->addCheckboxType();

        // username for ajax validation
        $helper->field('username')->addHiddenType();
    }

    protected function getLabelPrefix(): ?string
    {
        return null;
    }

    /**
     * Conditional validation depending on the check password checkbox.
     */
    private function validate(ExecutionContextInterface $context): void
    {
        /** @psalm-var FormInterface<mixed> $root */
        $root = $context->getRoot();

        // must check password?
        if (!(bool) $root->get('checkPassword')->getData()) {
            return;
        }

        // check password
        $password = $root->get('plainPassword');
        $violations = $context->getValidator()
            ->validate($password->getData(), new NotCompromisedPassword());

        // if compromised, assign the error to the password field
        if ($violations->count() > 0) {
            $password->addError(new FormError((string) $violations));
        }
    }
}
