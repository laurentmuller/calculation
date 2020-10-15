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

namespace App\Form\Product;

use App\Entity\Product;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Product edit type.
 *
 * @author Laurent Muller
 */
class ProductType extends AbstractEntityType
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
    protected function addFormFields(FormHelper $helper, FormBuilderInterface $builder, array $options): void
    {
        $helper->field('description')
            ->maxLength(255)
            ->addTextType();

        $helper->field('unit')
            ->autocomplete('off')
            ->maxLength(15)
            ->notRequired()
            ->addTextType();

        $helper->field('price')
            ->addMoneyType();

        $helper->field('category')
            ->addCategoryType();

        $helper->field('supplier')
            ->autocomplete('off')
            ->maxLength(255)
            ->notRequired()
            ->addTextType();
    }
}
