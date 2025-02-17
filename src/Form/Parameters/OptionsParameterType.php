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

use App\Form\FormHelper;
use App\Parameter\OptionsParameter;

class OptionsParameterType extends AbstractParameterType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $this->addCheckboxType(
            $helper,
            'qrCode',
            'parameters.fields.qr_code',
            'parameters.helps.qr_code'
        );
        $this->addCheckboxType(
            $helper,
            'printAddress',
            'parameters.fields.print_address',
            'parameters.helps.print_address'
        );
    }

    #[\Override]
    protected function getParameterClass(): string
    {
        return OptionsParameter::class;
    }
}
