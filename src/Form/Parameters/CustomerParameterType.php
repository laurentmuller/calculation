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

namespace App\Form\Parameters;

use App\Form\FormHelper;
use App\Parameter\CustomerParameter;

class CustomerParameterType extends AbstractParameterType
{
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('name')
            ->label('parameters.fields.customer_name')
            ->updateOption('prepend_icon', 'fa-solid fa-user-group')
            ->addTextType();

        $helper->field('address')
            ->label('parameters.fields.customer_address')
            ->updateOption('prepend_icon', 'fa-solid fa-location-dot')
            ->notRequired()
            ->addTextType();

        $helper->field('zipCity')
            ->label('parameters.fields.customer_zip_city')
            ->updateOption('prepend_icon', 'fa-solid fa-map-location-dot')
            ->notRequired()
            ->addTextType();

        $helper->field('phone')
            ->label('parameters.fields.customer_phone')
            ->updateOption('prepend_title', 'parameters.fields.customer_phone_title')
            ->notRequired()
            ->addTelType();

        $helper->field('email')
            ->label('parameters.fields.customer_email')
            ->updateOption('prepend_title', 'parameters.fields.customer_email_title')
            ->notRequired()
            ->addEmailType();

        $this->addUrlType(
            $helper,
            'url',
            'parameters.fields.customer_url',
            'parameters.fields.customer_url_title'
        );

        $this->addUrlType(
            $helper,
            'linkedin',
            'parameters.fields.customer_linkedin',
            'parameters.fields.customer_linkedin_title',
            'fa-brands fa-linkedin'
        );

        $this->addUrlType(
            $helper,
            'instagram',
            'parameters.fields.customer_instagram',
            'parameters.fields.customer_instagram_title',
            'fa-brands fa-instagram'
        );

        $this->addUrlType(
            $helper,
            'facebook',
            'parameters.fields.customer_facebook',
            'parameters.fields.customer_facebook_title',
            'fa-brands fa-facebook'
        );
    }

    protected function getParameterClass(): string
    {
        return CustomerParameter::class;
    }

    private function addUrlType(
        FormHelper $helper,
        string $field,
        string $label,
        string $title,
        ?string $icon = null
    ): void {
        $helper->field($field)
            ->label($label)
            ->updateOption('prepend_title', $title)
            ->notRequired()
            ->addUrlType(icon: $icon);
    }
}
