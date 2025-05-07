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
     * @phpstan-param array{simple_widget: bool, ...} $options
     */
    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['simple_widget']) {
            $form->remove('task')
                ->add('task', PlainType::class, $this->getPlainTypeOptions());
        }
        $view->vars['simple_widget'] = $options['simple_widget'];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('simple_widget', false)
            ->setAllowedTypes('simple_widget', 'bool');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('task')
            ->updateOption('query_all', false)
            ->add(TaskListType::class);

        $helper->field('quantity')
            ->addNumberType();
    }

    #[\Override]
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
