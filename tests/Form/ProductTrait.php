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

namespace App\Tests\Form;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Tests\Entity\IdTrait;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

trait ProductTrait
{
    use IdTrait;
    use ManagerRegistryTrait;

    private ?Product $product = null;

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getProductEntityType(): EntityType
    {
        return new EntityType($this->getProductRegistry());
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getProductRegistry(): MockObject&ManagerRegistry
    {
        return $this->createManagerRegistry(
            Product::class,
            ProductRepository::class,
            'getQueryBuilderByCategory',
            [$this->getProduct()]
        );
    }

    /**
     * @throws \ReflectionException
     */
    private function getProduct(): Product
    {
        if (!$this->product instanceof Product) {
            $this->product = new Product();
            $this->product->setDescription('Description');

            return $this->setId($this->product);
        }

        return $this->product;
    }
}
