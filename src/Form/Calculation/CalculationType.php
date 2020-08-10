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

use App\Entity\Calculation;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Calculation edit type.
 *
 * @author Laurent Muller
 */
class CalculationType extends AbstractEntityType
{
    /*
     * the empty user name
     */
    private string $emptyUser;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct(Calculation::class);
        $this->emptyUser = $translator->trans('calculation.edit.empty_user');
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper, FormBuilderInterface $builder, array $options): void
    {
        $helper->field('id')
            ->addHiddenType();

        $helper->field('date')
            ->addDateType();

        $helper->field('customer')
            ->maxLength(255)
            ->autocomplete('off')
            ->addTextType();

        $helper->field('description')
            ->maxLength(255)
            ->addTextType();

        $helper->field('userMargin')
            ->percent(true)
            ->addPercentType(-100, 300);

        $helper->field('state')
            ->addStateType();

        $helper->field('createdAt')
            ->addPlainType();

        $helper->field('createdBy')
            ->updateOption('empty_value', $this->emptyUser)
            ->addPlainType();

        $helper->field('updatedAt')
            ->addPlainType();

        $helper->field('updatedBy')
            ->updateOption('empty_value', $this->emptyUser)
            ->addPlainType();

        // groups
        $helper->field('groups')
            ->updateOption('prototype_name', '__groupIndex__')
            ->addCollectionType(CalculationGroupType::class);
    }
}
