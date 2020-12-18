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

namespace App\Form\Task;

use App\Form\AbstractHelperType;
use App\Form\Category\CategoryEntityType;
use App\Form\FormHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to compute a task.
 *
 * @author Laurent Muller
 */
class TaskServiceType extends AbstractHelperType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        if (false === $options['show_category']) {
            $builder->remove('category');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('show_category', false)
            ->addAllowedTypes('show_category', 'bool');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('task')
            ->add(TaskEntityType::class);

        $helper->field('category')
            ->add(CategoryEntityType::class);

        $helper->field('quantity')
            ->updateRowAttribute('class', 'text-right')
            ->updateAttribute('min', 1)
            ->addNumberType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): string
    {
        return 'taskcompute.fields.';
    }
}
