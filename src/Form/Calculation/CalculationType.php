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
use App\Interfaces\EntityInterface;

/**
 * Calculation edit type.
 *
 * @extends AbstractEntityType<Calculation>
 */
class CalculationType extends AbstractEntityType
{
    public function __construct()
    {
        parent::__construct(Calculation::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('date')
            ->addDatePointType();

        $helper->field('customer')
            ->maxLength(EntityInterface::MAX_STRING_LENGTH)
            ->autocomplete('off')
            ->addTextType();

        $helper->field('description')
            ->maxLength(EntityInterface::MAX_STRING_LENGTH)
            ->addTextType();

        $helper->field('userMargin')
            ->percent(true)
            ->addPercentType(-100, 300);

        $helper->field('state')
            ->add(CalculationStateListType::class);

        // groupes
        $helper->field('groups')
            ->updateOption('prototype_name', '__groupIndex__')
            ->addCollectionType(CalculationGroupType::class);
    }
}
