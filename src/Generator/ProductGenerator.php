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
use App\Util\FormatUtils;

/**
 * Class to generate products.
 *
 * @extends AbstractEntityGenerator<Product>
 */
class ProductGenerator extends AbstractEntityGenerator
{
    /**
     * {@inheritDoc}
     */
    protected function createEntities(int $count, bool $simulate, Generator $generator): array
    {
        $entities = [];
        for ($i = 0; $i < $count; ++$i) {
            $entity = new Product();
            $description = $this->getDescription($generator);
            $entity->setDescription($description)
                ->setPrice($generator->randomFloat(2, 1, 50))
                ->setSupplier($generator->productSupplier())
                ->setUnit($generator->productUnit())
                ->setCategory($generator->category());
            $entities[] = $entity;
        }

        return $entities;
    }

    protected function getCountMessage(int $count): string
    {
        return $this->trans('counters.products_generate', ['count' => $count]);
    }

    protected function mapEntity($entity): array
    {
        return [
            'description' => $entity->getDescription(),
            'group' => $entity->getGroupCode(),
            'category' => $entity->getCategoryCode(),
            'price' => FormatUtils::formatAmount($entity->getPrice()),
            'unit' => $entity->getUnit(),
        ];
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
