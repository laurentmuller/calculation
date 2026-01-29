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

use App\Entity\CalculationGroup;
use App\Form\Calculation\CalculationCategoryType;
use App\Form\Calculation\CalculationGroupType;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Tests\Form\EntityTypeTestCase;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends EntityTypeTestCase<CalculationGroup, CalculationGroupType>
 */
final class CalculationGroupTypeTest extends EntityTypeTestCase
{
    #[\Override]
    protected function getData(): array
    {
        return [
            'code' => null,
            'position' => 0,
            'categories' => new ArrayCollection(),
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return CalculationGroup::class;
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return CalculationGroupType::class;
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            new CalculationGroupType(self::createStub(GroupRepository::class)),
            new CalculationCategoryType(self::createStub(CategoryRepository::class)),
        ];
    }
}
