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
use App\Interfaces\EntityInterface;

/**
 * Task item edit type.
 *
 * @template-extends AbstractEntityType<TaskItem>
 */
class TaskItemType extends AbstractEntityType
{
    public function __construct()
    {
        parent::__construct(TaskItem::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('name')
            ->widgetClass('unique-name')
            ->maxLength(EntityInterface::MAX_STRING_LENGTH)
            ->addTextType();

        $helper->field('position')
            ->addHiddenType();

        $helper->field('margins')
            ->updateOption('prototype_name', '__marginIndex__')
            ->addCollectionType(TaskItemMarginType::class);
    }
}
