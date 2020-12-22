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

namespace App\Form\Dialog;

use App\Form\AbstractHelperType;
use App\Form\Category\CategoryEntityType;
use App\Form\FormHelper;
use App\Form\Task\TaskEntityType;

/**
 * Type to edit a calculation task in a dialog.
 *
 * @author Laurent Muller
 */
class EditTaskDialogType extends AbstractHelperType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'task';
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
