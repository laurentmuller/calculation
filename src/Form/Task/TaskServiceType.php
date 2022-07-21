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
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('simple_widget', false)
            ->setAllowedTypes('simple_widget', 'bool');
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
        $helper->field('task')
            ->add(TaskListType::class);

        $helper->field('quantity')
            ->addNumberType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): string
    {
        return 'taskcompute.fields.';
    }

    private function getPlainTypeOptions(): array
    {
        return [
            'expanded' => true,
            'hidden_input' => true,
            'attr' => ['class' => 'skip-reset'],
            'label' => 'taskcompute.fields.task',
            'value_transformer' => fn (Task $task): ?int => $task->getId(),
            'display_transformer' => fn (Task $task): ?string => $task->getName(),
        ];
    }
}
