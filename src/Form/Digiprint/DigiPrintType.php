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

use App\Entity\DigiPrint;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * DigiPrint edit type.
 *
 * @author Laurent Muller
 */
class DigiPrintType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(DigiPrint::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('format')
            ->addTextType();

        $helper->field('height')
            ->addNumberType(0);

        $helper->field('width')
            ->addNumberType(0);

        $helper->field('items')
            ->addCollectionType(DigiPrintItemType::class);
    }
}
