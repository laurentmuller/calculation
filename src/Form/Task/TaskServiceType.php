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

namespace App\Form\Task;

use App\Entity\Task;
use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Form\Type\PlainType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to compute a task.
 */
class TaskServiceType extends AbstractHelperType
{
    /**
     * @psalm-param FormView<\Symfony\Component\Form\FormTypeInterface> $view
     * @psalm-param FormInterface<\Symfony\Component\Form\FormTypeInterface> $form
     *
     * @phpstan-param FormView<\Symfony\Component\Form\FormTypeInterface<mixed>> $view
     * @phpstan-param FormInterface<\Symfony\Component\Form\FormTypeInterface<mixed>> $form
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
        if ($options['simple_widget']) {
            $form->remove('task')
                ->add('task', PlainType::class, $this->getPlainTypeOptions());
        }
        $view->vars['simple_widget'] = $options['simple_widget'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('simple_widget', false)
            ->setAllowedTypes('simple_widget', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('task')
            ->add(TaskListType::class);

        $helper->field('quantity')
            ->addNumberType();
    }

    protected function getLabelPrefix(): string
    {
        return 'task_compute.fields.';
    }

    private function getPlainTypeOptions(): array
    {
        return [
            'expanded' => true,
            'hidden_input' => true,
            'attr' => ['class' => 'skip-reset'],
            'label' => 'task_compute.fields.task',
            'value_transformer' => fn (Task $task): ?int => $task->getId(),
            'display_transformer' => fn (Task $task): ?string => $task->getName(),
        ];
    }
}
