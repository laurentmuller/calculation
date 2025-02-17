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

namespace App\Form;

/**
 * Abstract margin edit type.
 *
 * @template T of \App\Entity\AbstractMargin
 *
 * @template-extends AbstractEntityType<T>
 */
abstract class AbstractMarginType extends AbstractEntityType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('minimum')
            ->widgetClass('validate-minimum')
            ->addNumberType();

        $helper->field('maximum')
            ->widgetClass('validate-maximum')
            ->addNumberType();

        $helper->field('margin')
            ->percent(false)
            ->addPercentType(100);
    }

    #[\Override]
    protected function getLabelPrefix(): string
    {
        return 'globalmargin.fields.';
    }
}
