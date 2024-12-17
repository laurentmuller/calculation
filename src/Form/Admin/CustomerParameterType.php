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

namespace App\Form\Admin;

use App\Form\AbstractHelperType;
use App\Form\FormHelper;

class CustomerParameterType extends AbstractHelperType
{
    public function __construct()
    {
    }

    public function getBlockPrefix(): string
    {
        return 'customer';
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('name')
            ->updateOption('prepend_icon', 'fa-solid fa-user-group')
            ->addTextType();
        $helper->field('address')
            ->updateOption('prepend_icon', 'fa-solid fa-location-dot')
            ->notRequired()
            ->addTextType();
        $helper->field('zipCity')
            ->updateOption('prepend_icon', 'fa-solid fa-map-location-dot')
            ->notRequired()
            ->addTextType();
        $helper->field('phone')
            ->updateOption('prepend_title', 'parameters.fields.customer_phone_title')
            ->notRequired()
            ->addTelType();
        $helper->field('fax')
            ->notRequired()
            ->addFaxType();
        $helper->field('email')
            ->updateOption('prepend_title', 'parameters.fields.customer_email_title')
            ->notRequired()
            ->addEmailType();
        $helper->field('url')
            ->updateOption('prepend_title', 'parameters.fields.customer_url_title')
            ->notRequired()
            ->addUrlType();
    }

    protected function getLabelPrefix(): ?string
    {
        return 'parameters.fields.customer_';
    }
}
