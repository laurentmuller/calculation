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

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;

/**
 * Unit test for {@link Product} class.
 */
class ProductTest extends AbstractEntityValidatorTest
{
    public function testDuplicate(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $first = new Product();
        $first->setDescription('My Product')
            ->setCategory($category);

        try {
            $this->saveEntity($group);
            $this->saveEntity($category);
            $this->saveEntity($first);

            $second = new Product();
            $second->setDescription('My Product')
                ->setCategory($category);

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    public function testInvalidBoth(): void
    {
        $product = new Product();
        $this->validate($product, 2);
    }

    public function testInvalidCategory(): void
    {
        $product = new Product();
        $product->setDescription('My Product');
        $this->validate($product, 1);
    }

    public function testInvalidDescription(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $product = new Product();
        $product->setCategory($category);
        $this->validate($product, 1);
    }

    public function testNotDuplicate(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $category->setGroup($group);

        $first = new Product();
        $first->setDescription('My Product')
            ->setCategory($category);

        try {
            $this->saveEntity($group);
            $this->saveEntity($category);
            $this->saveEntity($first);

            $second = new Product();
            $second->setDescription('My Product 2')
                ->setCategory($category);

            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    public function testValid(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $product = new Product();
        $product->setDescription('product')
            ->setCategory($category);
        $this->validate($product, 0);
    }

    private function getCategory(Group $group): Category
    {
        $category = new Category();
        $category->setCode('category');
        $category->setGroup($group);

        return $category;
    }

    private function getGroup(): Group
    {
        $group = new Group();
        $group->setCode('group');

        return $group;
    }
}
