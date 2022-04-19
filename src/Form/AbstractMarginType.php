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
 * @author Laurent Muller
 *
 * @template T of \App\Entity\AbstractMargin
 * @template-extends AbstractEntityType<T>
 */
abstract class AbstractMarginType extends AbstractEntityType
{
    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('minimum')
            ->addNumberType();

        $helper->field('maximum')
            ->addNumberType();

        $helper->field('margin')
            ->percent(false)
            ->addPercentType(0);
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): string
    {
        return 'groupmargin.fields.';
    }
}
