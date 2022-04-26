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

namespace App\Form\CalculationState;

use App\Entity\CalculationState;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Calculation state edit type.
 *
 * @template-extends AbstractEntityType<CalculationState>
 */
class CalculationStateType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(CalculationState::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('code')
            ->maxLength(30)
            ->addTextType();

        $helper->field('description')
            ->maxLength(255)
            ->notRequired()
            ->addTextareaType();

        $helper->field('editable')
            ->addYesNoType();

        $helper->field('color')
            ->addColorType();
    }
}
