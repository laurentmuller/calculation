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
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends EntityTypeTestCase<CalculationState, CalculationStateType>
 */
#[CoversClass(CalculationStateType::class)]
class CalculationStateTypeTest extends EntityTypeTestCase
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