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
use App\Form\FormHelper;

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
}
