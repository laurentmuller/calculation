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

use App\Enums\StrengthLevel;
use App\Form\FormHelper;
use App\Parameter\SecurityParameter;

class SecurityParameterType extends AbstractParameterType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('captcha')
            ->label('parameters.fields.security_display_captcha')
            ->addTrueFalseType('parameters.display.show', 'parameters.display.hide');

        $helper->field('level')
            ->label('password.security_strength_level')
            ->addEnumType(StrengthLevel::class);

        $this->addCheckboxType($helper, 'letter', 'password.security_letters');
        $this->addCheckboxType($helper, 'caseDiff', 'password.security_case_diff');
        $this->addCheckboxType($helper, 'number', 'password.security_numbers');
        $this->addCheckboxType($helper, 'specialChar', 'password.security_special_char');
        $this->addCheckboxType($helper, 'email', 'password.security_email');
        $this->addCheckboxType($helper, 'compromised', 'password.security_compromised_password');
    }

    #[\Override]
    protected function getParameterClass(): string
    {
        return SecurityParameter::class;
    }
}
