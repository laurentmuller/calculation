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

namespace App\Tests\Fixture;

use App\Enums\EntityAction;
use App\Form\FormHelper;
use App\Form\Parameters\AbstractParameterType;

/**
 * @psalm-suppress MissingTemplateParam
 */
class FixtureParameterType extends AbstractParameterType
{
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('minMargin')
            ->addPercentType();
        $helper->field('action')
            ->addEnumType(EntityAction::class);
        $helper->field('name')
            ->addTextType();
        $this->addCheckboxType($helper, 'value', 'Value');
    }

    protected function getParameterClass(): string
    {
        return FixtureParameter::class;
    }
}
