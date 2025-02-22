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

use App\Entity\CalculationCategory;
use App\Form\Calculation\CalculationCategoryType;
use App\Repository\CategoryRepository;
use App\Tests\Form\EntityTypeTestCase;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends EntityTypeTestCase<CalculationCategory, CalculationCategoryType>
 */
class CalculationCategoryTypeTest extends EntityTypeTestCase
{
    #[\Override]
    protected function getData(): array
    {
        return [
            'code' => null,
            'position' => 0,
            'items' => new ArrayCollection(),
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return CalculationCategory::class;
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return CalculationCategoryType::class;
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            new CalculationCategoryType($this->createMock(CategoryRepository::class)),
        ];
    }
}
