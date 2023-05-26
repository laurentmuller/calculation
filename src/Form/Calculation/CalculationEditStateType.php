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

namespace App\Form\Calculation;

use App\Entity\Calculation;
use App\Form\AbstractEntityType;
use App\Form\CalculationState\CalculationStateListType;
use App\Form\FormHelper;
use App\Form\Type\PlainType;

/**
 * Edit calculation state type.
 *
 * @template-extends AbstractEntityType<Calculation>
 */
class CalculationEditStateType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Calculation::class);
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('id')
            ->updateOption('number_pattern', PlainType::NUMBER_IDENTIFIER)
            ->widgetClass('text-center')
            ->addPlainType(true);

        $helper->field('date')
            ->updateOption('time_format', PlainType::FORMAT_NONE)
            ->widgetClass('text-center')
            ->addPlainType(true);

        $helper->field('overallMargin')
            ->label('calculation.fields.margin')
            ->updateOption('number_pattern', PlainType::NUMBER_PERCENT)
            ->updateOption('percent_decimals', 0)
            ->widgetClass('text-end')
            ->addPlainType(true);

        $helper->field('overallTotal')
            ->updateOption('number_pattern', PlainType::NUMBER_AMOUNT)
            ->widgetClass('text-end')
            ->addPlainType(true);

        $helper->field('customer')
            ->addPlainType(true);

        $helper->field('description')
            ->addPlainType(true);

        $helper->field('state')
            ->label('calculation.state.newstate')
            ->add(CalculationStateListType::class);
    }
}
