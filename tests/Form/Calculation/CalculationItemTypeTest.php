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

namespace App\Tests\Form\Calculation;

use App\Entity\CalculationItem;
use App\Form\Calculation\CalculationItemType;
use App\Tests\Form\EntityTypeTestCase;

/**
 * @extends EntityTypeTestCase<CalculationItem, CalculationItemType>
 */
class CalculationItemTypeTest extends EntityTypeTestCase
{
    #[\Override]
    protected function getData(): array
    {
        return [
            'description' => 'description',
            'unit' => 'unit',
            'price' => 1.0,
            'quantity' => 1.0,
            'position' => 0,
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return CalculationItem::class;
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return CalculationItemType::class;
    }
}
