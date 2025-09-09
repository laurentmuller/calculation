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

namespace App\Form\Calculation;

use App\Entity\CalculationItem;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Calculation item edit type.
 *
 * @template-extends AbstractEntityType<CalculationItem>
 */
class CalculationItemType extends AbstractEntityType
{
    public function __construct()
    {
        parent::__construct(CalculationItem::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('description')
            ->addHiddenType();
        $helper->field('unit')
            ->addHiddenType();
        $helper->field('price')
            ->addHiddenType();
        $helper->field('quantity')
            ->addHiddenType();
        $helper->field('position')
            ->addHiddenType();
        $helper->field('total')
            ->disabled()
            ->addHiddenType();
    }
}
