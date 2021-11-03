<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
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
 * Unit test for {@link App\Entity\Product} class.
 *
 * @author Laurent Muller
 */
class ProductTest extends AbstractEntityValidatorTest
{
    public function testDuplicate(): void
    {
        $category = $this->getCategory();

        $first = new Product();
        $first->setDescription('My Product')
            ->setCategory($category);

        try {
            $this->saveEntity($category->getGroup());
            $this->saveEntity($category);
            $this->saveEntity($first);

            $second = new Product();
            $second->setDescription('My Product')
                ->setCategory($category);

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
        }
    }

    public function testInvalidBoth(): void
    {
        $product = new Product();

        $result = $this->validator->validate($product);
        $this->assertEquals(2, $result->count());
    }

    public function testInvalidCategory(): void
    {
        $product = new Product();
        $product->setDescription('My Product');
        $this->validate($product, 1);
    }

    public function testInvalidDescription(): void
    {
        $product = new Product();
        $product->setCategory($this->getCategory());
        $this->validate($product, 1);
    }

    public function testNotDuplicate(): void
    {
        $category = $this->getCategory();

        $first = new Product();
        $first->setDescription('My Product')
            ->setCategory($category);

        try {
            $this->saveEntity($category->getGroup());
            $this->saveEntity($category);
            $this->saveEntity($first);

            $second = new Product();
            $second->setDescription('My Product 2')
                ->setCategory($category);

            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
        }
    }

    public function testValid(): void
    {
        $product = new Product();
        $product->setDescription('My Product')
            ->setCategory($this->getCategory());
        $this->validate($product, 0);
    }

    private function getCategory(): Category
    {
        $category = new Category();
        $category->setCode('mycategory')
            ->setGroup($this->getGroup());

        return $category;
    }

    private function getGroup(): Group
    {
        $group = new Group();
        $group->setCode('mygroup');

        return $group;
    }
}
