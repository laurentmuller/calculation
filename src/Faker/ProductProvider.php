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

namespace App\Faker;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Product provider.
 *
 * @author Laurent Muller
 *
 *  @template-extends EntityProvider<Product>
 */
class ProductProvider extends EntityProvider
{

    protected static $productName = [
        'adjective' => ['Small', 'Ergonomic', 'Rustic', 'Intelligent', 'Gorgeous', 'Incredible', 'Fantastic', 'Practical', 'Sleek', 'Awesome', 'Enormous', 'Mediocre', 'Synergistic', 'Heavy Duty', 'Lightweight', 'Aerodynamic', 'Durable'],
        'material' => ['Steel', 'Wooden', 'Concrete', 'Plastic', 'Cotton', 'Granite', 'Rubber', 'Leather', 'Silk', 'Wool', 'Linen', 'Marble', 'Iron', 'Bronze', 'Copper', 'Aluminum', 'Paper'],
        'product' => ['Chair', 'Car', 'Computer', 'Gloves', 'Pants', 'Shirt', 'Table', 'Shoes', 'Hat', 'Plate', 'Knife', 'Bottle', 'Coat', 'Lamp', 'Keyboard', 'Bag', 'Bench', 'Clock', 'Watch', 'Wallet'],
    ];

    /**
     * Constructor.
     */
    public function __construct(Generator $generator, EntityManagerInterface $manager)
    {
        parent::__construct($generator, $manager, Product::class);
    }

    /**
     * Gets a random product.
     */
    public function product(): ?Product
    {
        return $this->entity();
    }

    /**
     * Returns if the given product's description exist.
     */
    public function productExist(string $description): bool
    {
        return null !== $this->getRepository()->findOneBy(['description' => $description]);
    }

    /**
     * Gets random products. The products are sorted by category code and description.
     *
     * @return Product[]
     */
    public function products(int $count = 1, bool $allowDuplicates = false): array
    {
        $products = $this->randomElements($this->getEntities(), $count, $allowDuplicates);

        \usort($products, static function (Product $a, Product $b) {
            $result = \strcasecmp($a->getCategoryCode(), $b->getCategoryCode());
            if (0 === $result) {
                return \strcasecmp($a->getDescription(), $b->getDescription());
            }

            return $result;
        });

        return $products;
    }

    /**
     * Gets the number of products.
     */
    public function productsCount(): int
    {
        return $this->count();
    }

    /**
     * Gets a random supplier.
     */
    public function productSupplier(): ?string
    {
        return $this->distinctValue('supplier');
    }

    /**
     * Gets a random unit.
     */
    public function productUnit(): ?string
    {
        return $this->distinctValue('unit');
    }

    /**
     * Gets a product name.
     */
    public function productName(): string
    {
        return static::randomElement(static::$productName['adjective'])
            . ' ' . static::randomElement(static::$productName['material'])
            . ' ' . static::randomElement(static::$productName['product']);
    }
}
