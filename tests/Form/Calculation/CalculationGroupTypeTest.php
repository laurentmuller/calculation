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
use PHPUnit\Framework\MockObject\Exception;

/**
 * @extends EntityTypeTestCase<CalculationGroup, CalculationGroupType>
 */
class CalculationGroupTypeTest extends EntityTypeTestCase
{
    protected function getData(): array
    {
        return [
            'code' => null,
            'position' => 0,
            'categories' => new ArrayCollection(),
        ];
    }

    protected function getEntityClass(): string
    {
        return CalculationGroup::class;
    }

    protected function getFormTypeClass(): string
    {
        return CalculationGroupType::class;
    }

    /**
     * @throws Exception
     */
    protected function getPreloadedExtensions(): array
    {
        return [
            new CalculationGroupType($this->createMock(GroupRepository::class)),
            new CalculationCategoryType($this->createMock(CategoryRepository::class)),
        ];
    }
}
