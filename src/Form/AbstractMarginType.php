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

namespace App\Form;

/**
 * Abstract margin edit type.
 *
 * @author Laurent Muller
 */
abstract class AbstractMarginType extends AbstractEntityType
{
    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('minimum')
            ->addMoneyType($this->currency());

        $helper->field('maximum')
            ->addMoneyType($this->currency());

        $helper->field('margin')
            ->percent($this->percent())
            ->addPercentType(0);
    }

    /**
     * Returns if the curreny symbol for the minimum and maximum is displayed.
     *
     * The default value is false.
     *
     * @return bool true to display; false to hide
     */
    protected function currency(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): string
    {
        return 'groupmargin.fields.';
    }

    /**
     * Returns if the percent symbol for the margin is displayed.
     *
     * The default value is false.
     *
     * @return bool true to display; false to hide
     */
    protected function percent(): bool
    {
        return false;
    }
}
