<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Form\Customer;

use App\Entity\Customer;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Customer edit type.
 *
 * @author Laurent Muller
 */
class CustomerType extends AbstractEntityType
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
    protected function addFormFields(FormHelper $helper): void
    {
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
