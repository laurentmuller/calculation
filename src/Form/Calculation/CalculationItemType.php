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

namespace App\Form\Calculation;

use App\Entity\CalculationItem;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Calculation item edit type.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityType<CalculationItem>
 */
class CalculationItemType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(CalculationItem::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('description')->addHiddenType()
            ->field('unit')->addHiddenType()
            ->field('price')->addHiddenType()
            ->field('quantity')->addHiddenType()
            ->field('total')->disabled()->addHiddenType();
    }
}
