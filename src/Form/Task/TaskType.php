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

use App\Entity\AbstractEntity;
use App\Entity\Task;
use App\Form\AbstractEntityType;
use App\Form\Category\CategoryListType;
use App\Form\FormHelper;

/**
 * Task edit type.
 *
 * @template-extends AbstractEntityType<Task>
 */
class TaskType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Task::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('name')
            ->maxLength(AbstractEntity::MAX_STRING_LENGTH)
            ->addTextType();

        $helper->field('category')
            ->add(CategoryListType::class);

        $helper->field('unit')
            ->autocomplete('off')
            ->notRequired()
            ->maxLength(15)
            ->addTextType();

        $helper->field('supplier')
            ->autocomplete('off')
            ->maxLength(AbstractEntity::MAX_STRING_LENGTH)
            ->notRequired()
            ->addTextType();

        $helper->field('items')
            ->updateOption('prototype_name', '__itemIndex__')
            ->addCollectionType(TaskItemType::class);
    }
}
