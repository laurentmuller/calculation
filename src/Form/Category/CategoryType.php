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

namespace App\Form\Category;

use App\Entity\Category;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;
use App\Form\Group\GroupListType;
use App\Interfaces\EntityInterface;

/**
 * Category edit type.
 *
 * @template-extends AbstractEntityType<Category>
 */
class CategoryType extends AbstractEntityType
{
    public function __construct()
    {
        parent::__construct(Category::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('code')
            ->maxLength(EntityInterface::MAX_CODE_LENGTH)
            ->addTextType();

        $helper->field('description')
            ->maxLength(EntityInterface::MAX_STRING_LENGTH)
            ->notRequired()
            ->addTextareaType();

        $helper->field('group')
            ->add(GroupListType::class);
    }
}
