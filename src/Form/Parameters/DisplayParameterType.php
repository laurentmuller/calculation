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

use App\Enums\EntityAction;
use App\Enums\TableView;
use App\Form\FormHelper;
use App\Parameter\DisplayParameter;

/**
 * @extends AbstractParameterType<DisplayParameter>
 */
class DisplayParameterType extends AbstractParameterType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('displayMode')
            ->label('parameters.fields.display_mode')
            ->addEnumType(TableView::class);

        $helper->field('editAction')
            ->label('parameters.fields.edit_action')
            ->addEnumType(EntityAction::class);
    }

    #[\Override]
    protected function getParameterClass(): string
    {
        return DisplayParameter::class;
    }
}
