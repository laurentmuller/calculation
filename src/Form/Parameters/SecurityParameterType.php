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

use App\Constraint\Password;
use App\Enums\StrengthLevel;
use App\Form\FormHelper;
use App\Parameter\SecurityParameter;

/**
 * @extends AbstractParameterType<SecurityParameter>
 */
class SecurityParameterType extends AbstractParameterType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('captcha')
            ->label('parameters.fields.security_display_captcha')
            ->updateOption('prepend_icon', 'fa-solid fa-key')
            ->addTrueFalseType('parameters.display.show', 'parameters.display.hide');

        $helper->field('level')
            ->label('password.strengthLevel')
            ->updateOption('prepend_icon', 'fa-solid fa-hand-fist')
            ->addEnumType(StrengthLevel::class);

        foreach (Password::ALLOWED_OPTIONS as $option) {
            $this->addCheckboxType($helper, $option, 'password.' . $option);
        }
        $this->addCheckboxType($helper, 'compromised', 'password.compromisedPassword');
    }

    #[\Override]
    protected function getParameterClass(): string
    {
        return SecurityParameter::class;
    }
}
