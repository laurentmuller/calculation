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

use App\Entity\Customer;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Customer edit type.
 *
 * @author Laurent Muller
 */
class CustomerType extends BaseType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Customer::class);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $helper = new FormHelper($builder, 'customer.fields.');
        $helper->field('title')
            ->maxLength(50)
            ->notRequired()
            ->addTextType();

        $helper->field('lastName')
            ->className('customer-group')
            ->maxLength(50)
            ->notRequired()
            ->addTextType();

        $helper->field('firstName')
            ->className('customer-group')
            ->maxLength(50)
            ->notRequired()
            ->addTextType();

        $helper->field('company')
            ->className('customer-group')
            ->maxLength(255)
            ->notRequired()
            ->addTextType();

        $helper->field('address')
            ->autocomplete('disabled')
            ->maxLength(255)
            ->notRequired()
            ->addTextareaType();

        $helper->field('zipCode')
            ->autocomplete('disabled')
            ->maxLength(10)
            ->notRequired()
            ->addTextType();

        $helper->field('city')
            ->autocomplete('disabled')
            ->maxLength(255)
            ->notRequired()
            ->addTextType();

        $helper->field('email')
            ->maxLength(100)
            ->notRequired()
            ->addEmailType();

        $helper->field('webSite')
            ->maxLength(100)
            ->notRequired()
            ->addUrlType();
    }
}
