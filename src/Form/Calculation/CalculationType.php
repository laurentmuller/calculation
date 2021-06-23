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

namespace App\Form\Calculation;

use App\Entity\Calculation;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Calculation edit type.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityType<Calculation>
 */
class CalculationType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Calculation::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('id')
            ->addHiddenType();

        $helper->field('date')
            ->addDateType();

        $helper->field('customer')
            ->maxLength(255)
            ->autocomplete('off')
            ->addTextType();

        $helper->field('description')
            ->maxLength(255)
            ->addTextType();

        $helper->field('userMargin')
            ->percent(true)
            ->addPercentType(-100, 300);

        $helper->field('state')
            ->addCalculationStateListType();

        // groups
        $helper->field('groups')
            ->updateOption('prototype_name', '__groupIndex__')
            ->addCollectionType(CalculationGroupType::class);
    }
}
