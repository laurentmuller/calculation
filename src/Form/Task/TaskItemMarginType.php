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

use App\Entity\TaskItemMargin;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Task item margin edit type.
 *
 * @extends AbstractEntityType<TaskItemMargin>
 */
class TaskItemMarginType extends AbstractEntityType
{
    public function __construct()
    {
        parent::__construct(TaskItemMargin::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        // must-validate class?
        $helper->field('minimum')
            ->widgetClass('validate-minimum')
            ->addNumberType();

        $helper->field('maximum')
            ->widgetClass('validate-maximum')
            ->addNumberType();

        $helper->field('value')
            ->addNumberType();
    }
}
