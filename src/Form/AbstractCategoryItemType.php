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

namespace App\Form;

use App\Entity\AbstractCategoryItemEntity;
use App\Form\Category\CategoryListType;
use App\Interfaces\EntityInterface;

/**
 * Abstract category item type.
 *
 * @template TEntity of AbstractCategoryItemEntity
 *
 * @extends AbstractEntityType<TEntity>
 */
abstract class AbstractCategoryItemType extends AbstractEntityType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('category')
            ->add(CategoryListType::class);
        $helper->field('unit')
            ->autocomplete('off')
            ->maxLength(15)
            ->notRequired()
            ->addTextType();
        $helper->field('supplier')
            ->autocomplete('off')
            ->updateOption('prepend_icon', 'fa-solid fa-dolly')
            ->maxLength(EntityInterface::MAX_STRING_LENGTH)
            ->notRequired()
            ->addTextType();
    }
}
