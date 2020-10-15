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

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Abstract margin edit type.
 *
 * @author Laurent Muller
 */
abstract class AbstractMarginType extends AbstractEntityType
{
    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper, FormBuilderInterface $builder, array $options): void
    {
        $helper->field('minimum')
            ->addMoneyType($this->currency());

        $helper->field('maximum')
            ->addMoneyType($this->currency());

        $helper->field('margin')
            ->percent($this->percent())
            ->addPercentType(0);
    }

    /**
     * Returns if the curreny symbol for the minimum and maximum is displayed.
     *
     * The default value is false.
     *
     * @return bool true to display; false to hide
     */
    protected function currency(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): string
    {
        return 'categorymargin.fields.';
    }

    /**
     * Returns if the percent symbol for the margin is displayed.
     *
     * The default value is false.
     *
     * @return bool true to display; false to hide
     */
    protected function percent(): bool
    {
        return false;
    }
}
