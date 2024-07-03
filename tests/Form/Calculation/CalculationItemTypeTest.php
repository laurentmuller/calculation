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
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends EntityTypeTestCase<CalculationItem, CalculationItemType>
 */
#[CoversClass(CalculationItemType::class)]
class CalculationItemTypeTest extends EntityTypeTestCase
{
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

    protected function getEntityClass(): string
    {
        return CalculationItem::class;
    }

    protected function getFormTypeClass(): string
    {
        return CalculationItemType::class;
    }
}
