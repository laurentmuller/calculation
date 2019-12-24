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

use App\Entity\CalculationState;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Calculation state edit type.
 *
 * @author Laurent Muller
 */
class CalculationStateType extends BaseType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(CalculationState::class);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $helper = new FormHelper($builder);
        $helper->field('code')
            ->label('calculationstate.fields.code')
            ->maxLength(30)
            ->addTextType();

        $helper->field('description')
            ->label('calculationstate.fields.description')
            ->maxLength(255)
            ->notRequired()
            ->addTextareaType();

        $helper->field('editable')
            ->label('calculationstate.fields.editable')
            ->addYesNoType();

        $helper->field('color')
            ->label('calculationstate.fields.color')
            ->addColorType();
    }
}
