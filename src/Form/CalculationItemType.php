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

use App\Entity\CalculationItem;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Calculation item edit type.
 *
 * @author Laurent Muller
 */
class CalculationItemType extends BaseType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(CalculationItem::class);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $helper = new FormHelper($builder);
        $helper->field('description')->addHiddenType();
        $helper->field('unit')->addHiddenType();
        $helper->field('price')->addHiddenType();
        $helper->field('quantity')->addHiddenType();
        $helper->field('total')
            ->disabled()
            ->addHiddenType();
    }
}
