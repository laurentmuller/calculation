<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Form\Customer;

use App\Entity\AbstractEntity;
use App\Entity\Customer;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Customer edit type.
 *
 * @template-extends AbstractEntityType<Customer>
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
            ->autocomplete('disabled')
            ->addTextType();

        $helper->field('lastName')
            ->widgetClass('customer-group')
            ->maxLength(50)
            ->notRequired()
            ->addTextType();

        $helper->field('firstName')
            ->widgetClass('customer-group')
            ->maxLength(50)
            ->notRequired()
            ->addTextType();

        $helper->field('company')
            ->widgetClass('customer-group')
            ->maxLength(AbstractEntity::MAX_STRING_LENGTH)
            ->notRequired()
            ->addTextType();

        $helper->field('address')
            ->autocomplete('disabled')
            ->maxLength(AbstractEntity::MAX_STRING_LENGTH)
            ->notRequired()
            ->addTextareaType();

        $helper->field('zipCode')
            ->autocomplete('disabled')
            ->maxLength(10)
            ->notRequired()
            ->addTextType();

        $helper->field('city')
            ->autocomplete('disabled')
            ->maxLength(AbstractEntity::MAX_STRING_LENGTH)
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
