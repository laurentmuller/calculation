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

namespace App\Generator;

use App\Entity\Product;
use App\Faker\Generator;
use App\Interfaces\EntityInterface;
use App\Utils\FormatUtils;

/**
 * Class to generate products.
 *
 * @extends AbstractEntityGenerator<Product>
 */
class ProductGenerator extends AbstractEntityGenerator
{
    #[\Override]
    protected function createEntities(int $count, bool $simulate, Generator $generator): array
    {
        $entities = [];
        for ($i = 0; $i < $count; ++$i) {
            $entities[] = $this->createEntity($generator);
        }

        return $entities;
    }

    #[\Override]
    protected function getCountMessage(int $count): string
    {
        return $this->trans('counters.products_generate', ['count' => $count]);
    }

    #[\Override]
    protected function mapEntity(EntityInterface $entity): array
    {
        return [
            'description' => $entity->description,
            'group' => $entity->getGroupCode(),
            'category' => $entity->getCategoryCode(),
            'price' => FormatUtils::formatAmount($entity->price),
            'unit' => $entity->unit,
        ];
    }

    private function createEntity(Generator $generator): Product
    {
        return new Product()
            ->withDescription($this->getDescription($generator))
            ->withPrice($generator->randomFloat(2, 1, 50))
            ->withSupplier($generator->productSupplier())
            ->withUnit($generator->productUnit())
            ->withCategory($generator->category());
    }

    /**
     * Gets a product's description.
     */
    private function getDescription(Generator $generator): string
    {
        $try = 0;
        $description = $generator->productName();
        while ($try < 10 && $generator->productExist($description)) {
            $description = $generator->productName();
            ++$try;
        }

        return $description;
    }
}
