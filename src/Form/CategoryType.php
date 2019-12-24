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

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Category edit type.
 *
 * @author Laurent Muller
 */
class CategoryType extends BaseType
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $helper = new FormHelper($builder);
        $helper->field('code')
            ->label('category.fields.code')
            ->maxLength(30)
            ->addTextType();

        $helper->field('description')
            ->label('category.fields.description')
            ->notRequired()
            ->maxLength(255)
            ->addTextareaType();

        // margins
        $helper->field('margins')
            ->addCollectionType(CategoryMarginType::class);
    }
}
