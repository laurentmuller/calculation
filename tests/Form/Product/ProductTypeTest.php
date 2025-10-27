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

namespace App\Tests\Form\Product;

use App\Entity\Product;
use App\Form\Product\ProductType;
use App\Tests\Form\Category\CategoryTrait;
use App\Tests\Form\EntityTypeTestCase;

/**
 * @extends EntityTypeTestCase<Product, ProductType>
 */
final class ProductTypeTest extends EntityTypeTestCase
{
    use CategoryTrait;

    #[\Override]
    protected function getData(): array
    {
        return [
            'description' => 'description',
            'unit' => 'unit',
            'price' => 1.25,
            'category' => null,
            'supplier' => 'supplier',
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return Product::class;
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return ProductType::class;
    }

    /**
     * @throws \ReflectionException
     */
    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->getCategoryEntityType(),
        ];
    }
}
