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

namespace App\Form\Calculation;

use App\Entity\CalculationItem;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Calculation item edit type.
 *
 * @author Laurent Muller
 */
class CalculationItemType extends AbstractEntityType
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
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('description')->addHiddenType()
            ->field('unit')->addHiddenType()
            ->field('price')->addHiddenType()
            ->field('quantity')->addHiddenType()
            ->field('total')->disabled()->addHiddenType();
    }
}
