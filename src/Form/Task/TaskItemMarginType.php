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

use App\Entity\TaskItemMargin;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Task item margin edit type.
 *
 * @author Laurent Muller
 */
class TaskItemMarginType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(TaskItemMargin::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('minimum')
            ->updateAttribute('class', 'form-control-sm')
            ->addMoneyType();

        $helper->field('maximum')
            ->updateAttribute('class', 'form-control-sm')
            ->addMoneyType();

        $helper->field('value')
            ->updateAttribute('class', 'form-control-sm')
            ->addMoneyType();
    }
}
