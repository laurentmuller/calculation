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

namespace App\Tests\Form;

use App\Entity\CalculationState;
use App\Form\CalculationState\CalculationStateType;

/**
 * Test fo the {@link CalculationStateType} class.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CalculationStateTypeTest extends AbstractEntityTypeTestCase
{
    protected function getData(): array
    {
        return [
            'code' => 'code',
            'color' => 'black',
            'description' => 'description',
            'editable' => true,
        ];
    }

    protected function getEntityClass(): string
    {
        return CalculationState::class;
    }

    protected function getFormTypeClass(): string
    {
        return CalculationStateType::class;
    }
}
