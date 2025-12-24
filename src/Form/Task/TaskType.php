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
use App\Form\AbstractCategoryItemType;
use App\Form\FormHelper;
use App\Interfaces\EntityInterface;

/**
 * Task edit type.
 *
 * @extends AbstractCategoryItemType<Task>
 */
class TaskType extends AbstractCategoryItemType
{
    public function __construct()
    {
        parent::__construct(Task::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        parent::addFormFields($helper);
        $helper->field('name')
            ->maxLength(EntityInterface::MAX_STRING_LENGTH)
            ->addTextType();
        $helper->field('items')
            ->addCollectionType(TaskItemType::class, '__itemIndex__');
    }
}
