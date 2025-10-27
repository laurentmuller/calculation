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

namespace App\Tests\Form\CalculationState;

use App\Entity\CalculationState;
use App\Form\CalculationState\CalculationStateType;
use App\Tests\Form\EntityTypeTestCase;

/**
 * @extends EntityTypeTestCase<CalculationState, CalculationStateType>
 */
final class CalculationStateTypeTest extends EntityTypeTestCase
{
    #[\Override]
    protected function getData(): array
    {
        return [
            'code' => 'code',
            'color' => 'black',
            'description' => 'description',
            'editable' => true,
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return CalculationState::class;
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return CalculationStateType::class;
    }
}
