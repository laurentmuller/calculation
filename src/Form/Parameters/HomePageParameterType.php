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
use App\Parameter\HomePageParameter;

class HomePageParameterType extends AbstractParameterType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('calculations')
            ->label('parameters.fields.calculations')
            ->help('parameters.helps.calculations')
            ->labelClass('radio-inline')
            ->updateOptions([
                'choice_translation_domain' => false,
                'expanded' => true,
            ])
            ->addChoiceType($this->getCalculationChoices());

        $this->addCheckboxType($helper, 'panelState', 'index.panel_state', 'parameters.helps.panel_state');
        $this->addCheckboxType($helper, 'panelMonth', 'index.panel_month', 'parameters.helps.panel_month');
        $this->addCheckboxType($helper, 'panelCatalog', 'index.panel_catalog', 'parameters.helps.panel_catalog');
        $this->addCheckboxType($helper, 'statusBar', 'parameters.fields.status_bar', 'parameters.helps.status_bar');
        $this->addCheckboxType($helper, 'darkNavigation', 'parameters.fields.dark_navigation', 'parameters.helps.dark_navigation');
    }

    #[\Override]
    protected function getParameterClass(): string
    {
        return HomePageParameter::class;
    }

    /**
     * Gets the displayed calculations choices.
     */
    private function getCalculationChoices(): array
    {
        $values = \range(4, 20, 4);

        return \array_combine($values, $values);
    }
}
