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
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\ConstraintViolationListInterface;
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

    private function mapViolations(ConstraintViolationListInterface $list): string
    {
        $str = '';
        foreach ($list as $violation) {
            $str .= \sprintf("%s\n", $violation->getMessage());
        }

        return \trim($str);
    }

    private function validate(ExecutionContextInterface $context): void
    {
        $root = $context->getRoot();
        $form = $root->get('plainPassword');
        $password = (string) $form->getData();
        $target = $form->get('first');

        $security = $this->parameters->getSecurity();
        $constraint = $security->getStrengthConstraint();
        if (!$this->validateConstraint($context, $constraint, $password, $target)) {
            return;
        }
        $constraint = $security->getPasswordConstraint();
        if (!$this->validateConstraint($context, $constraint, $password, $target)) {
            return;
        }
        if ($security->isCompromised()) {
            $constraint = new NotCompromisedPassword();
            $this->validateConstraint($context, $constraint, $password, $target);
        }
    }

    /**
     * @phpstan-param FormInterface<array> $target
     */
    private function validateConstraint(
        ExecutionContextInterface $context,
        Constraint $constraint,
        string $password,
        FormInterface $target
    ): bool {
        $violations = $context->getValidator()
            ->validate($password, $constraint);
        if (0 === $violations->count()) {
            return true;
        }

        $error = $this->mapViolations($violations);
        $target->addError(new FormError($error));

        return false;
    }
}
