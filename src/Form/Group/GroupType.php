<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Form\Group;

use App\Entity\Category;
use App\Form\AbstractEntityType;
use App\Form\Category\CategoryMarginType;
use App\Form\FormHelper;

/**
 * Root category (group) edit type.
 *
 * @author Laurent Muller
 */
class GroupType extends AbstractEntityType
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

        $helper->field('margins')
            ->addCollectionType(CategoryMarginType::class);
    }
}
