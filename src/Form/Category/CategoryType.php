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

namespace App\Form\Category;

use App\Entity\Category;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;
use App\Form\Group\GroupListType;

/**
 * Category edit type.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityType<Category>
 */
class CategoryType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Category::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('code')
            ->maxLength(30)
            ->addTextType();

        $helper->field('description')
            ->notRequired()
            ->maxLength(255)
            ->addTextareaType();

        $helper->field('group')
            ->add(GroupListType::class);
    }
}
