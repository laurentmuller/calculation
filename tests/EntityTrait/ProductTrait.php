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

namespace App\Tests\EntityTrait;

use App\Entity\Category;
use App\Entity\Product;

/**
 * Trait to manage a product.
 */
trait ProductTrait
{
    use CategoryTrait;

    private ?Product $product = null;

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function getProduct(Category $category, float $price = 1.0, string $description = 'Test description'): Product
    {
        if (!$this->product instanceof Product) {
            $this->product = new Product();
            $this->product->setCategory($category)
                ->setPrice($price)
                ->setDescription($description);
            $this->addEntity($this->product);
        }

        return $this->product; // @phpstan-ignore-line
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteProduct(): void
    {
        if ($this->product instanceof Product) {
            $this->product = $this->deleteEntity($this->product);
        }
        $this->deleteCategory();
    }
}
