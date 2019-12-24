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

use App\Entity\Product;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Product edit type.
 *
 * @author Laurent Muller
 */
class ProductType extends BaseType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Product::class);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $helper = new FormHelper($builder);

        $helper->field('description')
            ->label('product.fields.description')
            ->maxLength(255)
            ->addTextType();

        $helper->field('unit')
            ->label('product.fields.unit')
            ->autocomplete('off')
            ->maxLength(15)
            ->notRequired()
            ->addTextType();

        $helper->field('price')
            ->label('product.fields.price')
            ->currency(false)
            ->addMoneyType();

        $helper->field('category')
            ->label('product.fields.category')
            ->addCategoryType();

        $helper->field('supplier')
            ->label('product.fields.supplier')
            ->autocomplete('off')
            ->maxLength(255)
            ->notRequired()
            ->addTextType();
    }
}
