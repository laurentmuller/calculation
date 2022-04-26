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

namespace App\Faker;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Product provider.
 *
 * @template-extends EntityProvider<Product>
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ProductProvider extends EntityProvider
{
    /** @var string[] */
    private static array $adjective = ['Petit', 'Ergonomique', 'Rustique', 'Intelligent', 'Magnifique', 'Incroyable', 'Fantastique', 'Pratique', 'Elégant', 'Génial', 'Enorme', 'Médiocre', 'Synergique', 'Robuste', 'Léger', 'Aérodynamique', 'Durable'];

    /** @var string[] */
    private static array $material = ['acier', 'bois', 'béton', 'plastique', 'coton', 'granit', 'caoutchouc', 'cuir', 'soie', 'laine', 'lin', 'marbre', 'fer', 'bronze', 'cuivre', 'aluminium', 'papier'];

    /** @var string[] */
    private static array $product = ['tabouret', 'camoin', 'ordinateur', 'gants', 'pantalon', 'chemisier', 'tabouret', 'chausse-pied', 'chapeau', 'vase', 'couteau', 'récipient', 'manteau', 'carnet', 'clavier', 'sac', 'banc', 'stylo', 'boîtier', 'portefeuille'];

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
     * Gets a random product's name.
     */
    public function productName(): string
    {
        $adjective = (string) static::randomElement(self::$adjective);
        $product = (string) static::randomElement(self::$product);
        $material = (string) static::randomElement(self::$material);

        return "$adjective $product en $material";
    }

    /**
     * Gets random products. The products are sorted by category code and description.
     *
     * @return Product[]
     */
    public function products(int $count = 1, bool $allowDuplicates = false): array
    {
        /** @var Product[] $products */
        $products = $this->randomElements($this->getEntities(), $count, $allowDuplicates);
        if (\count($products) < 2) {
            return $products;
        }

        \usort($products, static function (Product $a, Product $b) {
            $result = \strcasecmp((string) $a->getCategoryCode(), (string) $b->getCategoryCode());
            if (0 === $result) {
                return \strcasecmp((string) $a->getDescription(), (string) $b->getDescription());
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
     * Gets a random product's supplier.
     */
    public function productSupplier(): ?string
    {
        /** @var string|null $value */
        $value = $this->distinctValue('supplier', true);

        return $value;
    }

    /**
     * Gets a random product's unit.
     */
    public function productUnit(): ?string
    {
        /** @var string|null $value */
        $value = $this->distinctValue('unit', true);

        return $value;
    }
}
