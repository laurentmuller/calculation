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
use App\Parameter\ApplicationParameters;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends AbstractEntityType<User>
 */
abstract class AbstractChangePasswordType extends AbstractEntityType
{
    public function __construct(private readonly ApplicationParameters $parameters)
    {
        parent::__construct(User::class);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'constraints' => [
                new Callback(fn (mixed $object, ExecutionContextInterface $context) => $this->validate($context)),
            ],
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('plainPassword')
            ->addRepeatPasswordType('user.password.new', 'user.password.new_confirmation');
    }

    private function validate(ExecutionContextInterface $context): void
    {
        /** @phpstan-var FormInterface<mixed> $form */
        $form = $context->getRoot()->get('plainPassword');
        $password = (string) $form->getData();
        $target = $form->get('first');

        $security = $this->parameters->getSecurity();
        if ($security->isStrengthConstraint()) {
            $this->validateConstraint($context, $password, $security->getStrengthConstraint(), $target);
        }
        if ($security->isPasswordConstraint()) {
            $this->validateConstraint($context, $password, $security->getPasswordConstraint(true), $target);
        }
        if ($security->isCompromised()) {
            $this->validateConstraint($context, $password, $security->getNotCompromisedConstraint(), $target);
        }
    }

    /**
     * @phpstan-param FormInterface<mixed> $target
     */
    private function validateConstraint(
        ExecutionContextInterface $context,
        string $password,
        Constraint $constraint,
        FormInterface $target
    ): void {
        $violations = $context->getValidator()
            ->validate($password, $constraint);
        foreach ($violations as $violation) {
            $target->addError(new FormError((string) $violation->getMessage()));
        }
    }
}
