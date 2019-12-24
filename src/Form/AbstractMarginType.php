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

use App\Entity\AbstractMargin;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Abstract margin edit type.
 *
 * @author Laurent Muller
 */
abstract class AbstractMarginType extends BaseType
{
    /**
     * Constructor.
     *
     * @param string $className the entity class name. Must be a subclass of AbstractMargin class.
     */
    protected function __construct($className)
    {
        $className = empty($className) ? AbstractMargin::class : $className;
        parent::__construct($className);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $helper = new FormHelper($builder);
        $currency = $this->currency() ? $helper->getCurrencySymbol() : false;

        $helper->field('minimum')
            ->label('categorymargin.fields.minimum')
            ->currency($currency)
            ->addMoneyType();

        $helper->field('maximum')
            ->label('categorymargin.fields.maximum')
            ->currency($currency)
            ->addMoneyType();

        $helper->field('margin')
            ->label('categorymargin.fields.margin')
            ->percent($this->percent())
            ->addPercentType(0);
    }

    /**
     * Returns if the curreny symbol for the minimum and maximum is displayed.
     * The default value is false.
     *
     * @return bool true to display; false to hide
     */
    protected function currency(): bool
    {
        return false;
    }

    /**
     * Returns if the percent symbol for the margin is displayed.
     * The default value is false.
     *
     * @return bool true to display; false to hide
     */
    protected function percent(): bool
    {
        return false;
    }
}
