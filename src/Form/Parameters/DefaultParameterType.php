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

use App\Form\CalculationState\CalculationStateListType;
use App\Form\Category\CategoryListType;
use App\Form\FormHelper;
use App\Parameter\DefaultParameter;

class DefaultParameterType extends AbstractParameterType
{
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('state')
            ->label('parameters.fields.default_state')
            ->add(CalculationStateListType::class);

        $helper->field('category')
            ->label('parameters.fields.default_category')
            ->add(CategoryListType::class);

        $helper->field('minMargin')
            ->label('parameters.fields.minimum_margin')
            ->percent(true)
            ->addPercentType(0);
    }

    protected function getParameterClass(): string
    {
        return DefaultParameter::class;
    }
}
