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

namespace App\Form\Dialog;

use App\Form\AbstractHelperType;
use App\Form\FormHelper;

/**
 * Type to edit a calculation item in a dialog.
 *
 * @author Laurent Muller
 */
class EditItemDialogType extends AbstractHelperType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'item';
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('description')
            ->notMapped()
            ->addTextType();

        $helper->field('unit')
            ->notRequired()
            ->addTextType();

        $helper->field('category')
            ->addCategoryType();

        $helper->field('price')
            ->addNumberType();

        $helper->field('quantity')
            ->addNumberType();

        $helper->field('total')
            ->className('form-control-plaintext text-right border rounded px-2')
            ->disabled()
            ->notRequired()
            ->addTextType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): string
    {
        return 'calculationitem.fields.';
    }
}
