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

namespace App\Form\Dialog;

use App\Form\AbstractHelperType;
use App\Form\Category\CategoryListType;
use App\Form\FormHelper;

/**
 * Type to edit a calculation item in a dialog.
 */
class EditItemDialogType extends AbstractHelperType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'item';
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('description')
            ->addTextType();

        $helper->field('unit')
            ->notRequired()
            ->maxLength(15)
            ->addTextType();

        $helper->field('category')
            ->add(CategoryListType::class);

        $helper->field('price')
            ->addNumberType();

        $helper->field('quantity')
            ->addNumberType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): string
    {
        return 'calculationitem.fields.';
    }
}
