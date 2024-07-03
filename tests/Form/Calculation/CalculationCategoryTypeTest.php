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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\PreloadedExtension;

/**
 * @extends EntityTypeTestCase<CalculationCategory, CalculationCategoryType>
 */
#[CoversClass(CalculationCategoryType::class)]
class CalculationCategoryTypeTest extends EntityTypeTestCase
{
    protected function getData(): array
    {
        return [
            'code' => null,
            'position' => 0,
            'items' => new ArrayCollection(),
        ];
    }

    protected function getEntityClass(): string
    {
        return CalculationCategory::class;
    }

    /**
     * @throws Exception
     */
    protected function getExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getExtensions();
        $types = [
            new CalculationCategoryType($this->createMock(CategoryRepository::class)),
        ];
        $extensions[] = new PreloadedExtension($types, []);

        return $extensions;
    }

    protected function getFormTypeClass(): string
    {
        return CalculationCategoryType::class;
    }
}
