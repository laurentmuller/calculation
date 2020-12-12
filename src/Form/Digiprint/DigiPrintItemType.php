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

namespace App\Form\Digiprint;

use App\Entity\DigiPrintItem;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * DigiPrintItem edit type.
 *
 * @author Laurent Muller
 */
class DigiPrintItemType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(DigiPrintItem::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('type')
            ->addHiddenType();

        $helper->field('minimum')
            ->addNumberType(0);

        $helper->field('maximum')
            ->addNumberType(0);

        $helper->field('amount')
            ->addMoneyType();
    }
}
