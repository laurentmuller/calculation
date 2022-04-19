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

use App\Entity\TaskItem;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Task item edit type.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityType<TaskItem>
 */
class TaskItemType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(TaskItem::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('name')
            ->widgetClass('unique-name')
            ->maxLength(255)
            ->addTextType();

        $helper->field('position')
            ->addHiddenType();

        $helper->field('margins')
            ->updateOption('prototype_name', '__marginIndex__')
            ->addCollectionType(TaskItemMarginType::class);
    }
}
