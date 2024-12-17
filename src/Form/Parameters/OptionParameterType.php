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

namespace App\Form\Parameters;

use App\Form\AbstractHelperType;
use App\Form\FormHelper;

class OptionParameterType extends AbstractHelperType
{
    public function getBlockPrefix(): string
    {
        return 'option';
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('printAddress')
            // ->updateAttribute('data-default', $this->getDefaultValue('printAddress'))
            ->help('parameters.helps.qr_code')
            ->addCheckboxType();

        $helper->field('qrCode')
            // ->updateAttribute('data-default', $this->getDefaultValue('qrCode'))
            ->help('parameters.helps.print_address')
            ->addCheckboxType();
    }
}
