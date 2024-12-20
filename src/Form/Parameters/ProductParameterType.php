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
use App\Form\Product\ProductListType;
use App\Parameter\ProductParameter;

class ProductParameterType extends AbstractParameterType
{
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('product')
            ->label('parameters.fields.default_product')
            ->notRequired()
            ->widgetClass('must-validate')
            ->updateOption('placeholder', 'parameters.placeholders.default_product')
            ->updateAttribute('data-default', '')
            ->add(ProductListType::class);

        $helper->field('quantity')
            ->label('parameters.fields.default_product_quantity')
            ->widgetClass('input-number')
            ->addNumberType();

        $this->addCheckboxType($helper, 'edit', 'parameters.fields.default_product_edit');
    }

    protected function getParameterClass(): string
    {
        return ProductParameter::class;
    }
}
