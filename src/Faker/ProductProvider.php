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
use App\Repository\ProductRepository;

/**
 * Product provider.
 *
 * @template-extends EntityProvider<Product>
 */
class ProductProvider extends EntityProvider
{
    private const ADJECTIVE = [
        'Petit',
        'Ergonomique',
        'Rustique',
        'Intelligent',
        'Magnifique',
        'Incroyable',
        'Fantastique',
        'Pratique',
        'Elégant',
        'Génial',
        'Enorme',
        'Médiocre',
        'Synergique',
        'Robuste',
        'Léger',
        'Aérodynamique',
        'Durable'];

    private const MATERIAL = [
        'acier',
        'bois',
        'béton',
        'plastique',
        'coton',
        'granit',
        'caoutchouc',
        'cuir',
        'soie',
        'laine',
        'lin',
        'marbre',
        'fer',
        'bronze',
        'cuivre',
        'aluminium',
        'papier'];

    private const PRODUCT = [
        'tabouret',
        'camion',
        'ordinateur',
        'gants',
        'pantalon',
        'chemisier',
        'tabouret',
        'chausse-pied',
        'chapeau',
        'vase',
        'couteau',
        'récipient',
        'manteau',
        'carnet',
        'clavier',
        'sac',
        'banc',
        'stylo',
        'boîtier',
        'portefeuille'];

    public function __construct(Generator $generator, ProductRepository $repository)
    {
        parent::__construct($generator, $repository);
    }

    /**
     * Gets a random product.
     */
    public function product(): ?Product
    {
        return $this->entity();
    }

    /**
     * Returns if the given product's description exists.
     *
     * @psalm-api
     */
    public function productExist(string $description): bool
    {
        return $this->repository->findOneBy(['description' => $description]) instanceof Product;
    }

    /**
     * Gets a random product's name.
     *
     * @psalm-api
     */
    public function productName(): string
    {
        $adjective = (string) static::randomElement(self::ADJECTIVE);
        $product = (string) static::randomElement(self::PRODUCT);
        $material = (string) static::randomElement(self::MATERIAL);

        return "$adjective $product en $material";
    }

    /**
     * Gets random products. The products are sorted by category code and description.
     *
     * @return Product[]
     */
    public function products(int $count = 1, bool $allowDuplicates = false): array
    {
        // products?
        if (0 === $this->count()) {
            return [];
        }

        /** @var Product[] $products */
        $products = static::randomElements($this->getEntities(), $count, $allowDuplicates);
        if (\count($products) < 2) {
            return $products;
        }

        \usort($products, static function (Product $a, Product $b): int {
            $result = \strnatcasecmp($a->getCategoryCode(), $b->getCategoryCode());
            if (0 !== $result) {
                return $result;
            }

            return $a->compare($b);
        });

        return $products;
    }

    /**
     * Gets a random product's supplier.
     */
    public function productSupplier(): ?string
    {
        /** @phpstan-var string|null */
        return $this->distinctValue('supplier', true);
    }

    /**
     * Gets a random product's unit.
     */
    public function productUnit(): ?string
    {
        /** @phpstan-var string|null */
        return $this->distinctValue('unit', true);
    }
}
