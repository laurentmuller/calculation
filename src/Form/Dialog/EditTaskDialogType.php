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

namespace App\Form\Dialog;

use App\Form\AbstractHelperType;
use App\Form\Category\CategoryListType;
use App\Form\FormHelper;
use App\Form\Task\TaskListType;

/**
 * Type to edit a calculation task in a dialog.
 */
class EditTaskDialogType extends AbstractHelperType
{
    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'task';
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('task')
            ->updateOption('query_all', false)
            ->add(TaskListType::class);

        $helper->field('category')
            ->add(CategoryListType::class);

        $helper->field('unit')
            ->notRequired()
            ->maxLength(15)
            ->addTextType();

        $helper->field('quantity')
            ->updateAttribute('min', 0)
            ->addNumberType();
    }

    #[\Override]
    protected function getLabelPrefix(): string
    {
        return 'task_compute.fields.';
    }
}
