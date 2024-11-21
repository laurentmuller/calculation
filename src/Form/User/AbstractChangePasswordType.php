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
use App\Service\ApplicationService;
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
    public function __construct(private readonly ApplicationService $service)
    {
        parent::__construct(User::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'constraints' => [
                new Callback($this->validate(...)),
            ],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('plainPassword')
            ->addRepeatPasswordType('user.password.new', 'user.password.new_confirmation');
        $helper->field('checkPassword')
            ->label('user.change_password.check_password')
            ->updateOption('data', true)
            ->notMapped()
            ->addCheckboxType();
    }

    private function mapViolations(ConstraintViolationListInterface $list): string
    {
        $str = '';
        foreach ($list as $violation) {
            $str .= \sprintf("%s\n", $violation->getMessage());
        }

        return \trim($str);
    }

    /**
     * Conditional validation depending on the check password checkbox.
     */
    private function validate(?User $user, ExecutionContextInterface $context): void
    {
        if (!$user instanceof User) {
            return;
        }

        /** @psalm-var FormInterface<mixed> $root */
        $root = $context->getRoot();
        $form = $root->get('plainPassword');
        $password = (string) $form->getData();

        $constraint = $this->service->getStrengthConstraint();
        if (!$this->validateConstraint($form, $password, $context, $constraint)) {
            return;
        }
        $constraint = $this->service->getPasswordConstraint();
        if (!$this->validateConstraint($form, $password, $context, $constraint)) {
            return;
        }
        if ((bool) $root->get('checkPassword')->getData()) {
            $constraint = new NotCompromisedPassword();
            $this->validateConstraint($form, $password, $context, $constraint);
        }
    }

    private function validateConstraint(
        FormInterface $form,
        string $password,
        ExecutionContextInterface $context,
        Constraint $constraint
    ): bool {
        $violations = $context->getValidator()
            ->validate($password, $constraint);
        if (0 === $violations->count()) {
            return true;
        }

        $child = $form->get('first');
        $error = $this->mapViolations($violations);
        $child->addError(new FormError($error));

        return false;
    }
}
