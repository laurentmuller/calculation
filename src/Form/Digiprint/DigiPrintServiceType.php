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

use App\Form\AbstractHelperType;
use App\Form\FormHelper;

/**
 * Type to compute a DigiPrint.
 *
 * @author Laurent Muller
 *
 * @see \App\Service\DigiPrintService
 */
class DigiPrintServiceType extends AbstractHelperType
{
    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('digiprint')
            ->add(DigiPrintEntityType::class);

        $helper->field('quantity')
            ->updateAttribute('min', 1)
            ->addNumberType(0);

        $helper->field('price')
            ->label('digiprint.items.price')
            ->updateRowAttribute('class', 'mt-1')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('blacklit')
            ->label('digiprint.items.blacklit')
            ->updateRowAttribute('class', 'mt-1')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('replicating')
            ->label('digiprint.items.replicating')
            ->updateRowAttribute('class', 'mt-1')
            ->notRequired()
            ->addCheckboxType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): string
    {
        return 'digiprint.compute.fields.';
    }
}
